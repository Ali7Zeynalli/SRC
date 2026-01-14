<?php

/**
 * LDAP Authentication System
 * Русский язык
 */

// Информация о языковом пакете
$lang_info = [
    'name' => 'Русский',            // Полное название языка
    'code' => 'ru',                 // Краткий код языка
    'locale' => 'ru_RU',            // Код локали
    'author' => 'System',           // Автор
    'direction' => 'ltr',           // Направление текста (ltr/rtl)
    'description' => 'Русский языковой пакет'  // Описание
];

$lang = [
    // Информация о языке
    'language_name' => $lang_info['name'],
    'language_code' => $lang_info['code'],
    'language_locale' => $lang_info['locale'],
    'language_author' => $lang_info['author'],
    'language_direction' => $lang_info['direction'],
    'language_description' => $lang_info['description'],
    
    // Общие
    'app_name' => 'Server Reporting and Controlling System',
    'welcome_message' => 'Добро пожаловать',
    'login_title' => 'S-RCS',

    
    // Login form
    'placeholder_username' => 'Имя пользователя',
    'placeholder_password' => 'Пароль',
    'label_username' => 'Имя пользователя',
    'label_password' => 'Пароль',
    'button_login' => 'Войти',
    'are_you_sure_you_want_to_delete_user' => 'Вы уверены, что хотите удалить этого пользователя?',
    // Error messages
    'error_username_required' => 'Требуется имя пользователя',
    'error_password_required' => 'Требуется пароль',
    'error_username_empty' => 'Имя пользователя не может быть пустым',
    'error_username_short' => 'Имя пользователя должно содержать не менее 3 символов',
    'error_password_empty' => 'Пароль не может быть пустым',
    'error_access_denied' => 'Доступ запрещен. Вы не являетесь членом разрешенных групп.',
    'error_secure_store' => 'Не удалось безопасно сохранить учетные данные',
    'error_login_failed' => 'Попытка входа не удалась',
    'error_login_title' => 'Ошибка входа',
    'error_login_check_credentials' => 'Ошибка входа. Проверьте ваши учетные данные.',
    
    // Success messages
    'success_title' => 'Успешно!',
    'success_login_message' => 'Вход в систему...',
    'success_login' => 'Успешный вход',
    
    // Labels
    'label_required_groups' => 'Требуемые группы',
    
    // LDAP Error Messages
    'ldap_connection_failed' => 'Не удалось подключиться к серверу LDAP',
    'ldap_ssl_config_missing' => 'Отсутствует конфигурация SSL для LDAP. Требуется безопасное подключение',
    'ldap_ssl_insecure' => 'Запрошена небезопасная конфигурация SSL для LDAP!',
    'ldap_ssl_verify_required' => 'Небезопасное подключение LDAP не разрешено. Требуется проверка сертификата',
    'ldap_self_signed_warning' => 'ПРЕДУПРЕЖДЕНИЕ: LDAP использует самоподписанные сертификаты. Безопасная, но не идеальная конфигурация',
    'ldap_auth_failed' => 'Ошибка аутентификации',
    'ldap_no_session' => 'Активная сессия не найдена',
    'ldap_session_expired' => 'Срок действия сессии истек. Пожалуйста, войдите снова',
    'ldap_auth_error' => 'Ошибка аутентификации. Пожалуйста, войдите снова',
    'ldap_retry_auth' => 'Попытка аутентификации не удалась, повторная попытка...',
    'ldap_auth_final_failed' => 'Попытка аутентификации не удалась',
    
    // Footer переводы
    'footer_copyright' => '2025 Ali Zeynalli. Все права защищены.',
    'footer_contact' => 'Контакты',
    'footer_feedback' => 'Обратная связь',
    'footer_version' => 'в',
    'footer_website' => 'Ali Zeynalli',
    
    // Header переводы
    'header_logout' => 'Выйти',
    
    // Security page translations
    'security_title' => 'Конфигурация системы',
    'security_subtitle' => 'Управление системными параметрами',
    'security_section_has' => 'раздел',
    'security_parameters_available' => 'параметров можно настроить',
    'security_add' => 'Добавить',
    'security_delete' => 'Удалить',
    'security_reset' => 'Сбросить',
    'security_save_changes' => 'Сохранить изменения',
    'security_documentation' => 'Документация',
    'security_more_info' => 'Подробнее',
    'security_documentation_desc' => 'Просмотрите документацию для получения дополнительной информации и инструкций',
    'security_ad_settings' => 'Интеграция с Active Directory',
    'security_db_settings' => 'Интеграция с базой данных',
    'security_app_settings' => 'Параметры приложения',
    'security_password_settings' => 'Параметры сброса пароля',
    'security_allowed_groups_desc' => 'Список AD групп, которым разрешен доступ к системе',
    'security_base_dn_desc' => 'Базовый DN для поиска в Active Directory',
    'security_account_suffix_desc' => 'Суффикс, используемый для полных имен пользовательских учетных записей',
    'security_use_ssl_desc' => 'Использовать SSL/TLS для безопасного соединения',
    'security_default_temp_password_desc' => 'Формат временного пароля, используемого при сбросе пароля',
    
    // JavaScript translations
    'js_saving' => 'Сохранение...',
    'js_config_updated' => 'Конфигурация успешно обновлена',
    'js_error' => 'Ошибка',
    'js_error_occurred' => 'Произошла ошибка. Попробуйте снова.',
    'js_reset_confirm_title' => 'Вы уверены, что хотите сбросить форму?',
    'js_reset_confirm_text' => 'Все изменения будут отменены',
    'js_reset_confirm_yes' => 'Да, сбросить',
    'js_reset_confirm_no' => 'Нет, отменить',
    'js_add_new_item_title' => 'Добавить новый элемент',
    'js_add_new_item_placeholder' => 'Введите новое значение',
    'js_add_new_item_add' => 'Добавить',
    'js_add_new_item_cancel' => 'Отменить',
    'js_add_new_item_required' => 'Пожалуйста, введите значение!',
    'js_add_new_item_exists' => 'Это значение уже существует!',
    'js_add_new_item_error' => 'Элемент не был добавлен',
    'js_add_new_item_success' => 'элемент успешно добавлен',
    'js_delete_select_items' => 'Пожалуйста, выберите элементы для удаления',
    'js_delete_min_groups' => 'Должна остаться хотя бы одна группа',
    'js_delete_confirm_title' => 'Вы уверены, что хотите удалить элемент?',
    'js_delete_confirm_text' => 'элементов будет удалено. Вы уверены?',
    'js_delete_confirm_message' => 'элементов будет удалено. Вы уверены?',
    'js_delete_confirm_yes' => 'Да, удалить',
    'js_delete_confirm_no' => 'Отменить',
    'js_delete_success' => 'элементов успешно удалено',
    'js_documentation_title' => 'Документация',
    
    // Security documentation translations
    'security_ad_settings_desc' => 'В этом разделе вы можете настроить параметры интеграции с Active Directory (AD).',
    'security_domain_controllers_desc' => 'Список серверов контроллеров домена AD для проверки подлинности. Если указано несколько контроллеров домена, система может переключиться на другие, если один сервер временно недоступен.',
    'security_admin_group_desc' => 'Пользователи, принадлежащие к этой группе, будут иметь права администратора. Введите полное имя группы в Active Directory.',
    'security_ssl_tip' => 'Совет: Использование SSL повышает безопасность панели управления.',
    'security_db_settings_desc' => 'В этом разделе вы можете настроить параметры подключения к базе данных.',
    'security_db_connection_params' => 'Параметры подключения',
    'security_db_host_desc' => 'Адрес сервера базы данных',
    'security_db_name_desc' => 'Имя используемой базы данных',
    'security_db_username_desc' => 'Имя пользователя для подключения к базе данных',
    'security_db_password_desc' => 'Пароль для подключения к базе данных',
    'security_db_password_warning' => 'Внимание: Оставление пароля пустым создает риск безопасности.',
    'security_app_settings_desc' => 'В этом разделе вы можете настроить общие параметры приложения.',
    'security_session_security' => 'Сессия и безопасность',
    'security_session_security_desc' => 'Эти параметры определяют общее поведение системы и уровень безопасности.',
    'security_debug_mode_warning' => 'Режим отладки должен использоваться только в среде разработки.',
    'security_password_settings_desc' => 'В этом разделе вы можете настроить параметры безопасности для процесса сброса пароля.',
    'security_temp_password' => 'Временный пароль',
    'security_temp_password_desc' => 'Укажите формат временного пароля, используемого при сбросе пароля.',
    'security_temp_password_tip' => 'Совет: Сложные и случайные временные пароли повышают безопасность.',
    'security_documentation_coming_soon' => 'Документация для этого раздела готовится...',

    'security_domain_controllers' => 'Контроллеры домена',
    'security_admin_group' => 'Группа администраторов',
    'security_allowed_groups' => 'Разрешенные группы',
    'security_base_dn' => 'Базовый DN',
    'security_account_suffix' => 'Суффикс учетной записи',
    'security_ldap_port' => 'Порт LDAP',
    'security_connection_timeout' => 'Таймаут подключения',
    'security_use_ssl' => 'Использовать SSL',
    'security_db_host' => 'Хост базы данных',
    'security_db_name' => 'Имя базы данных',
    'security_db_username' => 'Имя пользователя базы данных',
    'security_db_password' => 'Пароль базы данных',
    'security_db_charset' => 'Кодировка',
    'security_session_timeout' => 'Таймаут сессии (секунды)',
    'security_max_login_attempts' => 'Максимальное количество попыток входа',
    'security_login_block_duration' => 'Длительность блокировки входа (минуты)',
    'security_debug_mode' => 'Режим отладки',
    'security_page_size' => 'Размер страницы',
    'security_default_temp_password' => 'Временный пароль по умолчанию',
    'security_parameters' => 'параметров можно настроить',
    'security_reset' => 'Сбросить',
    'security_subtitle' => 'Управление параметрами безопасности системы и конфигурациями',
    'security_ad_settings' => 'Настройки Active Directory',
    'security_db_settings' => 'Настройки базы данных',
    'security_app_settings' => 'Настройки приложения',
    'security_password_settings' => 'Настройки пароля',

    // Page titles
    'page_title_system_config' => 'Конфигурация системы',
    'page_active_security' => 'Безопасность',

    // Icons
    'icon_shield' => 'Щит',
    'icon_info' => 'Информация',
    'icon_book' => 'Книга',
    'icon_arrow_right' => 'Стрелка вправо',

    // Loading states
    'loading_saving' => 'Сохранение...',
];

return $lang; 