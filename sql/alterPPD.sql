-- Add Prompt Payment Price to order 
-- All default value should be set in the alter script
ALTER TABLE `0_sales_order_details`
ADD COLUMN `ppd` double NOT NULL DEFAULT 0 COMMENT 'Prompt payment DISCOUNT (fractional)"
;

ALTER TABLE `0_sales_orders`
ADD COLUMN total_ppd double COMMENT 'total amount if PPD'
;

ALTER TABlE `0_debtor_trans_details`
ADD COLUMN `ppd` double NOT NULL DEFAULT 0 COMMENT 'Prompt payment DISCOUNT (0 if not)""
,ADD COLUMN `ppd_gst` double COMMENT 'tax deduction if PPD'
;

ALTER TABLE `0_debtor_trans`
ADD COLUMN `ov_ppd_amount` double COMMENT 'Net discount if PPD'
,ADD COLUMN `ov_ppd_gst` double COMMENT 'Tax discount if PPD'
;
