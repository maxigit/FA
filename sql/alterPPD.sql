-- Add Prompt Payment Price to order 
-- All default value should be set in the alter script
ALTER TABLE `0_sales_order_details`
ADD COLUMN `ppd` double NOT NULL DEFAULT 0 COMMENT 'unit price if PPD'
;

ALTER TABLE `0_sales_orders`
ADD COLUMN total_ppd double COMMENT 'total amount if PPD'
;

ALTER TABlE `0_debtor_trans_details`
ADD COLUMN `ppd` double NOT NULL DEFAULT 0 COMMENT 'unit price if PPD'
,ADD COLUMN `ppd_gst` double COMMENT 'tax per unit if PPD'
;

ALTER TABLE `0_debtor_trans`
ADD COLUMN `ov_ppd_amount` double COMMENT 'Net amount if PPD'
,ADD COLUMN `ov_ppd_gst` double COMMENT 'Tax amount if PPD'
;
