<?php
/**
 * ImportableConverter.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\File;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use InvalidArgumentException;
use Log;

/**
 * Class ImportableConverter
 */
class ImportableConverter
{
    /** @var AssetAccountMapper */
    private $assetMapper;
    /** @var array */
    private $config;
    /** @var CurrencyMapper */
    private $currencyMapper;
    /** @var TransactionCurrency */
    private $defaultCurrency;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $mappedValues;
    /** @var OpposingAccountMapper */
    private $opposingMapper;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Convert ImportTransaction to factory-compatible array.
     *
     * @param array $importables
     *
     * @return array
     */
    public function convert(array $importables): array
    {
        $total = \count($importables);
        Log::debug(sprintf('Going to convert %d import transactions', $total));
        $result = [];
        /** @var ImportTransaction $importable */
        foreach ($importables as $index => $importable) {
            Log::debug(sprintf('Now going to parse importable %d of %d', $index + 1, $total));
            try {
                $entry = $this->convertSingle($importable);
            } catch (FireflyException $e) {
                $this->repository->addErrorMessage($this->importJob, sprintf('Row #%d: %s', $index + 1, $e->getMessage()));
                continue;
            }
            if (null !== $entry) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
        $this->config    = $importJob->configuration;

        // repository is used for error messages
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);

        // asset account mapper can map asset accounts (makes sense right?)
        $this->assetMapper = app(AssetAccountMapper::class);
        $this->assetMapper->setUser($importJob->user);
        $this->assetMapper->setDefaultAccount($this->config['import-account'] ?? 0);

        // opposing account mapper:
        $this->opposingMapper = app(OpposingAccountMapper::class);
        $this->opposingMapper->setUser($importJob->user);

        // currency mapper:
        $this->currencyMapper = app(CurrencyMapper::class);
        $this->currencyMapper->setUser($importJob->user);
        $this->defaultCurrency = app('amount')->getDefaultCurrencyByUser($importJob->user);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $mappedValues
     */
    public function setMappedValues(array $mappedValues): void
    {
        $this->mappedValues = $mappedValues;
    }

    /**
     * @param ImportTransaction $importable
     *
     * @throws FireflyException
     * @return array
     */
    private function convertSingle(ImportTransaction $importable): array
    {
        Log::debug(sprintf('Description is: "%s"', $importable->description));
        $amount        = $importable->calculateAmount();
        $foreignAmount = $importable->calculateForeignAmount();
        if ('' === $amount) {
            $amount = $foreignAmount;
        }
        if ('' === $amount) {
            throw new FireflyException('No transaction amount information.');
        }

        $transactionType   = 'unknown';
        $accountId         = $this->verifyObjectId('account-id', $importable->accountId);
        $billId            = $this->verifyObjectId('bill-id', $importable->billId);
        $budgetId          = $this->verifyObjectId('budget-id', $importable->budgetId);
        $currencyId        = $this->verifyObjectId('currency-id', $importable->currencyId);
        $categoryId        = $this->verifyObjectId('category-id', $importable->categoryId);
        $foreignCurrencyId = $this->verifyObjectId('foreign-currency-id', $importable->foreignCurrencyId);
        $opposingId        = $this->verifyObjectId('opposing-id', $importable->opposingId);

        $source          = $this->assetMapper->map($accountId, $importable->getAccountData());
        $destination     = $this->opposingMapper->map($opposingId, $amount, $importable->getOpposingAccountData());
        $currency        = $this->currencyMapper->map($currencyId, $importable->getCurrencyData());
        $foreignCurrency = $this->currencyMapper->map($foreignCurrencyId, $importable->getForeignCurrencyData());

        if (null === $currency) {
            Log::debug(sprintf('Could not map currency, use default (%s)', $this->defaultCurrency->code));
            $currency = $this->defaultCurrency;
        }
        Log::debug(sprintf('"%s" (#%d) is source and "%s" (#%d) is destination.', $source->name, $source->id, $destination->name, $destination->id));

        if (bccomp($amount, '0') === 1) {
            // amount is positive? Then switch:
            [$destination, $source] = [$source, $destination];
            Log::debug(
                sprintf(
                    '%s is positive, so "%s" (#%d) is now source and "%s" (#%d) is now destination.',
                    $amount, $source->name, $source->id, $destination->name, $destination->id
                )
            );
        }

        if ($source->accountType->type === AccountType::ASSET && $destination->accountType->type === AccountType::ASSET) {
            Log::debug('Source and destination are asset accounts. This is a transfer.');
            $transactionType = 'transfer';
        }
        if ($source->accountType->type === AccountType::REVENUE) {
            Log::debug('Source is a revenue account. This is a deposit.');
            $transactionType = 'deposit';
        }
        if ($destination->accountType->type === AccountType::EXPENSE) {
            Log::debug('Destination is an expense account. This is a withdrawal.');
            $transactionType = 'withdrawal';
        }
        if ($destination->id === $source->id) {
            throw new FireflyException(
                sprintf(
                    'Source ("%s", #%d) and destination ("%s", #%d) are the same account.', $source->name, $source->id, $destination->name, $destination->id
                )
            );
        }

        if ($transactionType === 'unknown') {
            $message = sprintf(
                'Cannot determine transaction type. Source account is a %s, destination is a %s', $source->accountType->type, $destination->accountType->type
            );
            Log::error($message, ['source' => $source->toArray(), 'dest' => $destination->toArray()]);
            throw new FireflyException($message);
        }

        // throw error when both are he same

        try {
            $date = Carbon::createFromFormat($this->config['date-format'] ?? 'Ymd', $importable->date);
        } catch (InvalidDateException|InvalidArgumentException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $date = new Carbon;
        }


        $dateStr = $date->format('Y-m-d');

        return [
            'type'               => $transactionType,
            'date'               => $dateStr,
            'tags'               => $importable->tags,
            'user'               => $this->importJob->user_id,
            'notes'              => $importable->note,

            // all custom fields:
            'internal_reference' => $importable->meta['internal-reference'] ?? null,
            'sepa-cc'            => $importable->meta['sepa-cc'] ?? null,
            'sepa-ct-op'         => $importable->meta['sepa-ct-op'] ?? null,
            'sepa-ct-id'         => $importable->meta['sepa-ct-id'] ?? null,
            'sepa-db'            => $importable->meta['sepa-db'] ?? null,
            'sepa-country'       => $importable->meta['sepa-countru'] ?? null,
            'sepa-ep'            => $importable->meta['sepa-ep'] ?? null,
            'sepa-ci'            => $importable->meta['sepa-ci'] ?? null,
            'interest_date'      => $importable->meta['date-interest'] ?? null,
            'book_date'          => $importable->meta['date-book'] ?? null,
            'process_date'       => $importable->meta['date-process'] ?? null,
            'due_date'           => $importable->meta['date-due'] ?? null,
            'payment_date'       => $importable->meta['date-payment'] ?? null,
            'invoice_date'       => $importable->meta['date-invoice'] ?? null,
            'external_id'        => $importable->externalId,

            // journal data:
            'description'        => $importable->description,
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => $billId,
            'bill_name'          => null === $billId ? $importable->billName : null,

            // transaction data:
            'transactions'       => [
                [
                    'currency_id'           => $currency->id,
                    'currency_code'         => null,
                    'description'           => null,
                    'amount'                => $amount,
                    'budget_id'             => $budgetId,
                    'budget_name'           => null === $budgetId ? $importable->budgetName : null,
                    'category_id'           => $categoryId,
                    'category_name'         => null === $categoryId ? $importable->categoryName : null,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'foreign_currency_id'   => $foreignCurrencyId,
                    'foreign_currency_code' => null === $foreignCurrency ? null : $foreignCurrency->code,
                    'foreign_amount'        => $foreignAmount,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];
    }

    /**
     * A small function that verifies if this particular key (ID) is present in the list
     * of valid keys.
     *
     * @param string $key
     * @param int    $objectId
     *
     * @return int|null
     */
    private function verifyObjectId(string $key, int $objectId): ?int
    {
        if (isset($this->mappedValues[$key]) && \in_array($objectId, $this->mappedValues[$key], true)) {
            return $objectId;
        }

        return null;
    }


}