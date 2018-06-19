<?php

/**
 * form.php
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

return [
    // new user:
    'bank_name'                      => 'Название банка',
    'bank_balance'                   => 'Бaлaнc',
    'savings_balance'                => 'Сберегательный баланс',
    'credit_card_limit'              => 'Лимит кредитной карты',
    'automatch'                      => 'Автоматическое сопоставление',
    'skip'                           => 'Пропустить',
    'name'                           => 'Название',
    'active'                         => 'Активный',
    'amount_min'                     => 'Минимальная сумма',
    'amount_max'                     => 'Максимальная сумма',
    'match'                          => 'Ключи для связи',
    'strict'                         => 'Строгий режим',
    'repeat_freq'                    => 'Повторы',
    'journal_currency_id'            => 'Валюта',
    'currency_id'                    => 'Валюта',
    'transaction_currency_id'        => 'Валюта',
    'external_ip'                    => 'Внешний IP-адрес вашего сервера',
    'attachments'                    => 'Вложения',
    'journal_amount'                 => 'Сумма',
    'journal_source_account_name'    => 'Доходный счет (источник)',
    'journal_source_account_id'      => 'Основной счёт (источник)',
    'BIC'                            => 'BIC',
    'verify_password'                => 'Проверка безопасности паролей',
    'source_account'                 => 'Исходный счёт',
    'destination_account'            => 'Счёт назначения',
    'journal_destination_account_id' => 'Основной счёт (назначение)',
    'asset_destination_account'      => 'Основной счёт (назначение)',
    'asset_source_account'           => 'Основной счёт (источник)',
    'journal_description'            => 'Описание',
    'note'                           => 'Заметки',
    'split_journal'                  => 'Разделить эту транзакцию',
    'split_journal_explanation'      => 'Разделить эту транзакцию на несколько частей',
    'currency'                       => 'Валюта',
    'account_id'                     => 'Основной счёт',
    'budget_id'                      => 'Бюджет',
    'openingBalance'                 => 'Начальный баланс',
    'tagMode'                        => 'Режим метки',
    'tag_position'                   => 'Расположение метки',
    'virtualBalance'                 => 'Виртуальный баланс',
    'targetamount'                   => 'Целевая сумма',
    'accountRole'                    => 'Роль учётной записи',
    'openingBalanceDate'             => 'Дата начального баланса',
    'ccType'                         => 'План оплаты по кредитной карте',
    'ccMonthlyPaymentDate'           => 'Дата ежемесячного платежа по кредитной карте',
    'piggy_bank_id'                  => 'Копилка',
    'returnHere'                     => 'Вернуться сюда',
    'returnHereExplanation'          => 'После сохранения вернуться сюда и создать ещё одну аналогичную запись.',
    'returnHereUpdateExplanation'    => 'Вернуться на эту страницу после обновления.',
    'description'                    => 'Описание',
    'expense_account'                => 'Счет расходов',
    'revenue_account'                => 'Доходный счет',
    'decimal_places'                 => 'Количество цифр после точки',
    'exchange_rate_instruction'      => 'Иностранные валюты',
    'source_amount'                  => 'Сумма (источник)',
    'destination_amount'             => 'Сумма (назначение)',
    'native_amount'                  => 'Собственная сумма',
    'new_email_address'              => 'Новый адрес электронной почты',
    'verification'                   => 'Проверка',
    'api_key'                        => 'API-ключ',
    'remember_me'                    => 'Запомнить меня',

    'source_account_asset'        => 'Исходный счёт (основной счёт)',
    'destination_account_expense' => 'Счёт назначения (счёт расхода)',
    'destination_account_asset'   => 'Счёт назначения (основной счёт)',
    'source_account_revenue'      => 'Исходный счёт (счёт доходов)',
    'type'                        => 'Тип',
    'convert_Withdrawal'          => 'Конвертировать расход',
    'convert_Deposit'             => 'Конвертировать доход',
    'convert_Transfer'            => 'Конвертировать перевод',

    'amount'                     => 'Сумма',
    'foreign_amount'             => 'Сумму в иностранной валюте',
    'existing_attachments'       => 'Существующие вложения',
    'date'                       => 'Дата',
    'interest_date'              => 'Дата выплаты',
    'book_date'                  => 'Дата бронирования',
    'process_date'               => 'Дата обработки',
    'category'                   => 'Категория',
    'tags'                       => 'Метки',
    'deletePermanently'          => 'Удалить навсегда',
    'cancel'                     => 'Отмена',
    'targetdate'                 => 'Намеченная дата',
    'startdate'                  => 'Дата начала',
    'tag'                        => 'Тег',
    'under'                      => 'Под',
    'symbol'                     => 'Символ',
    'code'                       => 'Код',
    'iban'                       => 'IBAN',
    'accountNumber'              => 'Номер счета',
    'creditCardNumber'           => 'Номер кредитной карты',
    'has_headers'                => 'Заголовки',
    'date_format'                => 'Формат даты',
    'specifix'                   => 'Исправления, специфичные для банка или файла',
    'attachments[]'              => 'Вложения',
    'store_new_withdrawal'       => 'Сохранить новый расход',
    'store_new_deposit'          => 'Сохранить новый доход',
    'store_new_transfer'         => 'Сохранить новый перевод',
    'add_new_withdrawal'         => 'Добавить новый расход',
    'add_new_deposit'            => 'Добавить новый доход',
    'add_new_transfer'           => 'Добавить новый перевод',
    'title'                      => 'Заголовок',
    'notes'                      => 'Заметки',
    'filename'                   => 'Имя файла',
    'mime'                       => 'Тип Mime',
    'size'                       => 'Размер',
    'trigger'                    => 'Триггер',
    'stop_processing'            => 'Остановить обработку',
    'start_date'                 => 'Начало диапазона',
    'end_date'                   => 'Конец диапазона',
    'export_start_range'         => 'Начало диапазона для экспорта',
    'export_end_range'           => 'Конец диапазона для экспорта',
    'export_format'              => 'Формат файла',
    'include_attachments'        => 'Включить загруженные вложения',
    'include_old_uploads'        => 'Включить импортированные данные',
    'accounts'                   => 'Экспорт транзакций с этих счетов',
    'delete_account'             => 'Удалить счёт ":name"',
    'delete_bill'                => 'Удаление счёта к оплате ":name"',
    'delete_budget'              => 'Удалить бюджет ":name"',
    'delete_category'            => 'Удалить категорию ":name"',
    'delete_currency'            => 'Удалить валюту ":name"',
    'delete_journal'             => 'Удалить транзакцию с описанием ":description"',
    'delete_attachment'          => 'Удалить вложение ":name"',
    'delete_rule'                => 'Удалить правило ":title"',
    'delete_rule_group'          => 'Удалить группу правил ":title"',
    'delete_link_type'           => 'Удалить тип ссылки ":name"',
    'delete_user'                => 'Удалить пользователя ":email"',
    'user_areYouSure'            => 'Если вы удалите пользователя ":email", все данные будут удалены. Это действие нельзя будет отменить. Если вы удалите себя, вы потеряете доступ к этому экземпляру Firefly III.',
    'attachment_areYouSure'      => 'Вы действительно хотите удалить вложение с именем ":name"?',
    'account_areYouSure'         => 'Вы действительно хотите удалить счёт с именем ":name"?',
    'bill_areYouSure'            => 'Вы действительно хотите удалить счёт на оплату с именем ":name"?',
    'rule_areYouSure'            => 'Вы действительно хотите удалить правило с названием ":title"?',
    'ruleGroup_areYouSure'       => 'Вы действительно хотите удалить группу правил с названием ":title"?',
    'budget_areYouSure'          => 'Вы действительно хотите удалить бюджет с именем ":name"?',
    'category_areYouSure'        => 'Вы действительно хотите удалить категорию с именем ":name"?',
    'currency_areYouSure'        => 'Вы уверены, что хотите удалить валюту ":name"?',
    'piggyBank_areYouSure'       => 'Вы уверены, что хотите удалить копилку с именем ":name"?',
    'journal_areYouSure'         => 'Вы действительно хотите удалить транзакцию с описанием ":description"?',
    'mass_journal_are_you_sure'  => 'Вы действительно хотите удалить эти транзакции?',
    'tag_areYouSure'             => 'Вы действительно хотите удалить метку ":tag"?',
    'journal_link_areYouSure'    => 'Вы действительно хотите удалить связь между <a href=":source_link">:source</a> и <a href=":destination_link">:destination</a>?',
    'linkType_areYouSure'        => 'Вы уверены, что хотите удалить тип ссылки ":name" (":inward" / ":outward")?',
    'permDeleteWarning'          => 'Удаление информации из Firefly III является постоянным и не может быть отменено.',
    'mass_make_selection'        => 'Вы все же можете предотвратить удаление элементов, сняв флажок.',
    'delete_all_permanently'     => 'Удалить выбранное навсегда',
    'update_all_journals'        => 'Обновить эти транзакции',
    'also_delete_transactions'   => 'Будет удалена только транзакция, связанная с этим счётом.|Будут удалены все :count транзакций, связанные с этим счётом.',
    'also_delete_connections'    => 'Единственная транзакция, связанная с данным типом ссылки, потеряет это соединение. |Все :count транзакций, связанные с данным типом ссылки, потеряют свои соединения.',
    'also_delete_rules'          => 'Единственное правило, связанное с данной группой правил, будет удалено. |Все :count правила, связанные с данной группой правил, будут удалены.',
    'also_delete_piggyBanks'     => 'Единственная копилка, связанная с данным счётом, будет удалена.|Все :count копилки, связанные с данным счётом, будут удалены.',
    'bill_keep_transactions'     => 'Единственная транзакция, связанная с данным счётом, не будет удалена. |Все :count транзакции, связанные с данным счётом, будут сохранены.',
    'budget_keep_transactions'   => 'Единственная транзакция, связанная с данным бюджетом, не будет удалена.|Все :count транзакции, связанные с этим бюджетом, будут сохранены.',
    'category_keep_transactions' => 'Единственная транзакция, связанная с данной категорией, не будет удалена.|Все :count транзакции, связанные с этой категорией, будут сохранены.',
    'tag_keep_transactions'      => 'Только транзакция, связанная с этой меткой, будет удалена.|Все :count транзакций, связанные с этой меткой, будут удалены.',
    'check_for_updates'          => 'Проверить обновления',

    'email'                 => 'Адрес электронной почты',
    'password'              => 'Пароль',
    'password_confirmation' => 'Пароль (ещё раз)',
    'blocked'               => 'Заблокирован?',
    'blocked_code'          => 'Причина блокировки',

    // import
    'apply_rules'           => 'Применить правила',
    'artist'                => 'Исполнитель',
    'album'                 => 'Альбом',
    'song'                  => 'Композиция',


    // admin
    'domain'                => 'Домен',
    'single_user_mode'      => 'Отключить регистрацию пользователей',
    'is_demo_site'          => 'Это демо-сайт',

    // import
    'import_file'           => 'Файл импорта',
    'configuration_file'    => 'Файл конфигурации',
    'import_file_type'      => 'Тип файла для импорта',
    'csv_comma'             => 'Запятая (,)',
    'csv_semicolon'         => 'Точка с запятой (;)',
    'csv_tab'               => 'Табулятор (невидимый)',
    'csv_delimiter'         => 'Разделитель полей CSV',
    'csv_import_account'    => 'Профиль для импорта по умолчанию',
    'csv_config'            => 'Параметры импорта CSV',
    'client_id'             => 'ID клиента',
    'service_secret'        => 'Service secret',
    'app_secret'            => 'App secret',
    'app_id'                => 'ID приложения',
    'secret'                => 'Секретный ключ',
    'public_key'            => 'Открытый ключ',
    'country_code'          => 'Код страны',
    'provider_code'         => 'Банк или поставщик данных',

    'due_date'           => 'Срок',
    'payment_date'       => 'Дата платежа',
    'invoice_date'       => 'Дата выставления счёта',
    'internal_reference' => 'Внутренняя ссылка',
    'inward'             => 'Внутреннее описание',
    'outward'            => 'Внешнее описание',
    'rule_group_id'      => 'Группа правил',
];
