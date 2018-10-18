ALTER TABLE 0_locations
CHANGE COLUMN `delivery_date` `availability_date` DATE NULL
, CHANGE COLUMN `delivery_period` `availability_period` VARCHAR(11) NULL
;
