ALTER TABLE tasks ADD COLUMN affected_user_dn VARCHAR(255) NULL AFTER requester_name;
ALTER TABLE tasks ADD COLUMN affected_user_name VARCHAR(255) NULL AFTER affected_user_dn;
