<?php

/**
 * UserRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\User;


/**
 * Class UserRequest
 */
class UserRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        if (!auth()->check()) {
            return false; // @codeCoverageIgnore
        }
        /** @var User $user */
        $user = auth()->user();
        if (!$user->hasRole('owner')) {
            return false; // @codeCoverageIgnore
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            'email'        => $this->string('email'),
            'blocked'      => $this->boolean('blocked'),
            'blocked_code' => $this->string('blocked_code'),
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'email'        => 'required|email|unique:users,email,',
            'blocked'      => 'required|boolean',
            'blocked_code' => 'in:email_changed',
        ];
        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                $user           = $this->route()->parameter('user');
                $rules['email'] = 'required|email|unique:users,email,' . $user->id;
                break;
        }

        return $rules;
    }

}
