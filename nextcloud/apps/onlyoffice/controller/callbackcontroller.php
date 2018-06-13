<?php
/**
 *
 * (c) Copyright Ascensio System Limited 2010-2018
 *
 * This program is freeware. You can redistribute it and/or modify it under the terms of the GNU
 * General Public License (GPL) version 3 as published by the Free Software Foundation (https://www.gnu.org/copyleft/gpl.html).
 * In accordance with Section 7(a) of the GNU GPL its Section 15 shall be amended to the effect that
 * Ascensio System SIA expressly excludes the warranty of non-infringement of any third-party rights.
 *
 * THIS PROGRAM IS DISTRIBUTED WITHOUT ANY WARRANTY; WITHOUT EVEN THE IMPLIED WARRANTY OF MERCHANTABILITY OR
 * FITNESS FOR A PARTICULAR PURPOSE. For more details, see GNU GPL at https://www.gnu.org/copyleft/gpl.html
 *
 * You can contact Ascensio System SIA by email at sales@onlyoffice.com
 *
 * The interactive user interfaces in modified source and object code versions of ONLYOFFICE must display
 * Appropriate Legal Notices, as required under Section 5 of the GNU GPL version 3.
 *
 * Pursuant to Section 7 § 3(b) of the GNU GPL you must retain the original ONLYOFFICE logo which contains
 * relevant author attributions when distributing the software. If the display of the logo in its graphic
 * form is not reasonably feasible for technical reasons, you must include the words "Powered by ONLYOFFICE"
 * in every copy of the program you distribute.
 * Pursuant to Section 7 § 3(e) we decline to grant you any rights under trademark law for use of our trademarks.
 *
 */

namespace OCA\Onlyoffice\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;

use OCA\Onlyoffice\AppConfig;
use OCA\Onlyoffice\Crypt;
use OCA\Onlyoffice\DocumentService;

/**
 * Callback handler for the document server.
 * Download the file without authentication.
 * Save the file without authentication.
 */
class CallbackController extends Controller {

    /**
     * Root folder
     *
     * @var IRootFolder
     */
    private $root;

    /**
     * User session
     *
     * @var IUserSession
     */
    private $userSession;

    /**
     * User manager
     *
     * @var IUserManager
     */
    private $userManager;

    /**
     * l10n service
     *
     * @var IL10N
     */
    private $trans;

    /**
     * Logger
     *
     * @var OCP\ILogger
     */
    private $logger;

    /**
     * Application configuration
     *
     * @var OCA\Onlyoffice\AppConfig
     */
    private $config;

    /**
     * Hash generator
     *
     * @var OCA\Onlyoffice\Crypt
     */
    private $crypt;

    /**
     * Share manager
     *
     * @var OCP\Share\IManager
     */
    private $shareManager;

    /**
     * Status of the document
     *
     * @var Array
     */
    private $_trackerStatus = array(
        0 => "NotFound",
        1 => "Editing",
        2 => "MustSave",
        3 => "Corrupted",
        4 => "Closed"
    );

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - user session
     * @param IUserManager $userManager - user manager
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param OCA\Onlyoffice\AppConfig $config - application configuration
     * @param OCA\Onlyoffice\Crypt $crypt - hash generator
     * @param IManager $shareManager - Share manager
     */
    public function __construct($AppName, 
                                    IRequest $request,
                                    IRootFolder $root,
                                    IUserSession $userSession,
                                    IUserManager $userManager,
                                    IL10N $trans,
                                    ILogger $logger,
                                    AppConfig $config,
                                    Crypt $crypt,
                                    IManager $shareManager
                                    ) {
        parent::__construct($AppName, $request);

        $this->root = $root;
        $this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->crypt = $crypt;
        $this->shareManager = $shareManager;
    }


    /**
     * Downloading file by the document service
     *
     * @param string $doc - verification token with the file identifier
     *
     * @return DataDownloadResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function download($doc) {

        list ($hashData, $error) = $this->crypt->ReadHash($doc);
        if ($hashData === NULL) {
            $this->logger->info("Download with empty or not correct hash: " . $error, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
        }
        if ($hashData->action !== "download") {
            $this->logger->info("Download with other action", array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Invalid request")], Http::STATUS_BAD_REQUEST);
        }

        $fileId = $hashData->fileId;
        $this->logger->debug("Download: " . $fileId, array("app" => $this->appName));

        if (!empty($this->config->GetDocumentServerSecret())) {
            $header = \OC::$server->getRequest()->getHeader($this->config->JwtHeader());
            if (empty($header)) {
                $this->logger->info("Download without jwt", array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }

            $header = substr($header, strlen("Bearer "));

            try {
                $decodedHeader = \Firebase\JWT\JWT::decode($header, $this->config->GetDocumentServerSecret(), array("HS256"));
            } catch (\UnexpectedValueException $e) {
                $this->logger->info("Download with invalid jwt: " . $e->getMessage(), array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }
        }

        $userId = $hashData->userId;

        $token = $hashData->token;
        list ($file, $error) = empty($token) ? $this->getFile($userId, $fileId) : $this->getFileByToken($fileId, $token);

        if (isset($error)) {
            return $error;
        }

        try {
            return new DataDownloadResponse($file->getContent(), $file->getName(), $file->getMimeType());
        } catch (NotPermittedException  $e) {
            $this->logger->info("Download Not permitted: " . $fileId . " " . $e->getMessage(), array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Not permitted")], Http::STATUS_FORBIDDEN);
        }
        return new JSONResponse(["message" => $this->trans->t("Download failed")], Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * Downloading empty file by the document service
     *
     * @param string $doc - verification token with the file identifier
     *
     * @return OCA\Onlyoffice\DownloadResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function emptyfile($doc) {
        $this->logger->debug("Download empty", array("app" => $this->appName));

        list ($hashData, $error) = $this->crypt->ReadHash($doc);
        if ($hashData === NULL) {
            $this->logger->info("Download empty with empty or not correct hash: " . $error, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
        }
        if ($hashData->action !== "empty") {
            $this->logger->info("Download empty with other action", array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Invalid request")], Http::STATUS_BAD_REQUEST);
        }

        if (!empty($this->config->GetDocumentServerSecret())) {
            $header = \OC::$server->getRequest()->getHeader($this->config->JwtHeader());
            if (empty($header)) {
                $this->logger->info("Download empty without jwt", array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }

            $header = substr($header, strlen("Bearer "));

            try {
                $decodedHeader = \Firebase\JWT\JWT::decode($header, $this->config->GetDocumentServerSecret(), array("HS256"));
            } catch (\UnexpectedValueException $e) {
                $this->logger->info("Download empty with invalid jwt: " . $e->getMessage(), array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }
        }

        $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "en" . DIRECTORY_SEPARATOR . "new.docx";

        $template = file_get_contents($templatePath);
        if (!$template) {
            $this->logger->info("Template for download empty not found: " . $templatePath, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("File not found")], Http::STATUS_NOT_FOUND);
        }

        try {
            return new DataDownloadResponse($template, "new.docx", "application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        } catch (NotPermittedException  $e) {
            $this->logger->info("Download Not permitted: " . $fileId . " " . $e->getMessage(), array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Not permitted")], Http::STATUS_FORBIDDEN);
        }
        return new JSONResponse(["message" => $this->trans->t("Download failed")], Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * Handle request from the document server with the document status information
     *
     * @param string $doc - verification token with the file identifier
     * @param array $users - the list of the identifiers of the users
     * @param string $key - the edited document identifier
     * @param string $url - the link to the edited document to be saved
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function track($doc, $users, $key, $status, $url) {

        list ($hashData, $error) = $this->crypt->ReadHash($doc);
        if ($hashData === NULL) {
            $this->logger->info("Track with empty or not correct hash: " . $error, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
        }
        if ($hashData->action !== "track") {
            $this->logger->info("Track with other action", array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Invalid request")], Http::STATUS_BAD_REQUEST);
        }

        $fileId = $hashData->fileId;
        $this->logger->debug("Track: " . $fileId . " status " . $status, array("app" => $this->appName));

        if (!empty($this->config->GetDocumentServerSecret())) {
            $header = \OC::$server->getRequest()->getHeader($this->config->JwtHeader());
            if (empty($header)) {
                $this->logger->info("Track without jwt", array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }

            $header = substr($header, strlen("Bearer "));

            try {
                $decodedHeader = \Firebase\JWT\JWT::decode($header, $this->config->GetDocumentServerSecret(), array("HS256"));
                $this->logger->debug("Track HEADER : " . json_encode($decodedHeader), array("app" => $this->appName));

                $payload = $decodedHeader->payload;
                $users = isset($payload->users) ? $payload->users : NULL;
                $key = $payload->key;
                $status = $payload->status;
                $url = isset($payload->url) ? $payload->url : NULL;
            } catch (\UnexpectedValueException $e) {
                $this->logger->info("Track with invalid jwt: " . $e->getMessage(), array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Access denied")], Http::STATUS_FORBIDDEN);
            }
        }

        $trackerStatus = $this->_trackerStatus[$status];

        $error = 1;
        switch ($trackerStatus) {
            case "MustSave":
            case "Corrupted":
                if (empty($url)) {
                    $this->logger->info("Track without url: " . $fileId . " status " . $trackerStatus, array("app" => $this->appName));
                    return new JSONResponse(["message" => $this->trans->t("Url not found")], Http::STATUS_BAD_REQUEST);
                }

                $userId = $users[0];
                $user = $this->userManager->get($userId);
                if (!empty($user)) {
                    $this->logger->info("setupFS " . $userId, array("app" => $this->appName));
                    \OC_Util::tearDownFS();
                    \OC_Util::setupFS($userId);

                    $this->userSession->setUser($user);
                } else {
                    $ownerId = $hashData->ownerId;

                    \OC_Util::tearDownFS();
                    \OC_Util::setupFS($ownerId);
                }

                $token = $hashData->token;
                list ($file, $error) = empty($token) ? $this->getFile($userId, $fileId) : $this->getFileByToken($fileId, $token);

                if (isset($error)) {
                    return $error;
                }

                $fileName = $file->getName();
                $curExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $downloadExt = strtolower(pathinfo($url, PATHINFO_EXTENSION));

                $documentService = new DocumentService($this->trans, $this->config);
                if ($downloadExt !== $curExt) {
                    $key =  DocumentService::GenerateRevisionId($fileId . $url);

                    try {
                        $url = $documentService->GetConvertedUri($url, $downloadExt, $curExt, $key);
                    } catch (\Exception $e) {
                        $this->logger->error("GetConvertedUri on save error: " . $e->getMessage(), array("app" => $this->appName));
                        return new JSONResponse(["message" => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
                    }
                }

                $documentServerUrl = $this->config->GetDocumentServerInternalUrl(true);
                if (!empty($documentServerUrl)) {
                    $from = $this->config->GetDocumentServerUrl();

                    if (!preg_match("/^https?:\/\//i", $from)) {
                        $parsedUrl = parse_url($url);
                        $from = $parsedUrl["scheme"] . "://" . $parsedUrl["host"] . (array_key_exists("port", $parsedUrl) ? (":" . $parsedUrl["port"]) : "") . $from;
                    }

                    if ($from !== $documentServerUrl)
                    {
                        $this->logger->debug("Replace in track from " . $from . " to " . $documentServerUrl, array("app" => $this->appName));
                        $url = str_replace($from, $documentServerUrl, $url);
                    }
                }

                if (($newData = $documentService->Request($url))) {
                    $file->putContent($newData);
                    $error = 0;
                }
                break;

            case "Editing":
            case "Closed":
                $error = 0;
                break;
        }

        return new JSONResponse(["error" => $error], Http::STATUS_OK);
    }


    /**
     * Getting file by identifier
     *
     * @param integer $userId - user identifier
     * @param integer $fileId - file identifier
     *
     * @return array
     */
    private function getFile($userId, $fileId) {
        if (empty($fileId)) {
            return [NULL, $this->trans->t("FileId is empty")];
        }

        $files = $this->root->getUserFolder($userId)->getById($fileId);
        if (empty($files)) {
            $this->logger->info("Files not found: " . $fileId, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("Files not found")], Http::STATUS_NOT_FOUND);
        }
        $file = $files[0];

        if (! $file instanceof File) {
            $this->logger->info("File not found: " . $fileId, array("app" => $this->appName));
            return new JSONResponse(["message" => $this->trans->t("File not found")], Http::STATUS_NOT_FOUND);
        }

        return [$file, NULL];
    }

    /**
     * Getting file by token
     *
     * @param integer $fileId - file identifier
     * @param string $token - access token
     *
     * @return array
     */
    private function getFileByToken($fileId, $token) {
        list ($share, $error) = $this->getShare($token);

        if (isset($error)) {
            return [NULL, $error];
        }

        $node = $share->getNode();

        if ($node instanceof Folder) {
            $file = $node->getById($fileId)[0];
        } else {
            $file = $node;
        }

        return [$file, NULL];
    }

    /**
     * Getting share by token
     *
     * @param string $token - access token
     *
     * @return array
     */
    private function getShare($token) {
        if (empty($token)) {
            return [NULL, $this->trans->t("FileId is empty")];
        }

        $share = $this->shareManager->getShareByToken($token);
        if ($share === NULL || $share === false) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file")];
        }

        return [$share, NULL];
    }
}