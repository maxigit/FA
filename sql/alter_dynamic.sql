-- Add now or never column for each customer.
-- Default is 0 (happy to wait)
ALTER TABLE 0_debtors_master
ADD COLUMN now_or_never TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;


select * from 0_debtors_master where debtor_no = 24\G


truncate 0_sql_trail

select * from 0_sql_trail

