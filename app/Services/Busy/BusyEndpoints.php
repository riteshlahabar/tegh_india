<?php

namespace App\Services\Busy;

final class BusyEndpoints
{
    public const VOUCHER_PURCHASE = '2';

    public const VOUCHER_SALE_RETURN = '3';

    public const VOUCHER_SALE = '9';

    public const VOUCHER_PURCHASE_RETURN = '10';

    public const VOUCHER_SALES_ORDER = '12';

    public const VOUCHER_PURCHASE_ORDER = '13';

    public const VOUCHER_RECEIPT = '14';

    public const VOUCHER_JOURNAL = '16';

    public const VOUCHER_PAYMENT = '19';

    /**
     * Apna Bazar Api Bundle V2 — every request in the Postman collection.
     *
     * @var array<string, array{name: string, group: string, path: string, mode: string, write: bool, params: list<string>}>
     */
    public const CATALOG = [
        'get_accounts' => [
            'name' => 'Get Account Masters List',
            'group' => 'Accounts',
            'path' => 'get_accounts.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['Alias'],
        ],
        'get_account_ledger' => [
            'name' => 'Get Account Ledger',
            'group' => 'Accounts',
            'path' => 'get_account_ledger.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['ERP_Alias', 'Start_date', 'End_date', 'Pdc'],
        ],
        'get_outstanding' => [
            'name' => 'Get Account Outstanding',
            'group' => 'Accounts',
            'path' => 'get_outstanding.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'get_account_group' => [
            'name' => 'Get Account Group List',
            'group' => 'Accounts',
            'path' => 'get_account_group.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'add_accounts' => [
            'name' => 'Add New Account Master',
            'group' => 'Accounts',
            'path' => 'add_acccounts.php',
            'mode' => 'form',
            'write' => true,
            'params' => ['ERP_code', 'Name', 'Alias', 'Print_name', 'Parent_grp_name', 'Parent_grp_code', 'Mobile', 'Email', 'Address1', 'Address2', 'Address3', 'Address4', 'State', 'Gst_no', 'Op_balance', 'Blocked', 'Deactive'],
        ],
        'get_items' => [
            'name' => 'Get Item Masters List',
            'group' => 'Items',
            'path' => 'get_items.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'get_item_group' => [
            'name' => 'Get Item Group List',
            'group' => 'Items',
            'path' => 'get_item_group.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'add_item' => [
            'name' => 'Add New Item Master',
            'group' => 'Items',
            'path' => 'add_item.php',
            'mode' => 'form',
            'write' => true,
            'params' => ['ERP_code', 'Name', 'Alias', 'Print_name', 'Parent_grp_name', 'Parent_grp_code', 'Unit', 'Tax_cat', 'Hsn', 'MRP', 'Saleprice', 'Disc_percentage', 'Op_stock', 'Enable_param', 'Enable_batch', 'Enable_serialno', 'Blocked', 'Deactive'],
        ],
        'add_accounting_voucher' => [
            'name' => 'Add New Accounting Voucher',
            'group' => 'Transaction',
            'path' => 'add_accountingvoucher.php',
            'mode' => 'form',
            'write' => true,
            'params' => ['Act_code', 'Cash_bank_code', 'Tran_date', 'Vchtype', 'Narration', 'Voucher_amt'],
        ],
        'add_inventory_voucher' => [
            'name' => 'Add New Inventory Voucher',
            'group' => 'Transaction',
            'path' => 'add_inventoryvoucher.php',
            'mode' => 'form',
            'write' => true,
            'params' => ['Act_code', 'Tran_date', 'Vchtype', 'VoucherNo', 'Narration', 'Voucher_amt', 'Transport', 'Vehicle_no', 'Station', 'Grdate', 'Grno', 'Item_data'],
        ],
        'get_inventory_voucher_list' => [
            'name' => 'Get Inventory Voucher List',
            'group' => 'Transaction',
            'path' => 'get_inventoryvch_list.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['vchtype'],
        ],
        'get_accounting_voucher_list' => [
            'name' => 'Get Accounting Voucher List',
            'group' => 'Transaction',
            'path' => 'get_accountingvch_list.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['vchtype'],
        ],
        'get_busy_order_adjustment_details' => [
            'name' => 'Busy Order Adjustment Details',
            'group' => 'Reports',
            'path' => 'get_busy_order_adjustment_details.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['OrderNo', 'ItemErpCode'],
        ],
        'get_busy_challan_adjustment_details' => [
            'name' => 'Busy Challan Adjustment Details',
            'group' => 'Reports',
            'path' => 'get_busy_challan_adjustment_details.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['OrderNo', 'ItemErpCode'],
        ],
        'get_busy_all_order_adjustment' => [
            'name' => 'Busy All Order Adjustment Details',
            'group' => 'Reports',
            'path' => 'get_busy_allorder_adjustment.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['Start_date', 'End_date', 'ItemErpCode', 'ActErpCode'],
        ],
        'get_busy_all_challan_adjustment' => [
            'name' => 'Busy All Challan Adjustment Details',
            'group' => 'Reports',
            'path' => 'get_busy_allchallan_adjustment.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['Start_date', 'End_date', 'ItemErpCode', 'ActErpCode'],
        ],
        'get_item_stock_summary' => [
            'name' => 'Get Item Mc Wise Stock Summary',
            'group' => 'Reports',
            'path' => 'get_item_stock_summary.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['Alias'],
        ],
        'get_busy_pending_order' => [
            'name' => 'Get Busy Pending Order Summary',
            'group' => 'Reports',
            'path' => 'get_busypendingorder.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['Start_date', 'End_date'],
        ],
        'get_list_of_vouchers' => [
            'name' => 'Get Busy Vouchers List',
            'group' => 'Reports',
            'path' => 'get_listofvouchers.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['vchtype', 'Start_date', 'End_date'],
        ],
        'get_list_of_vouchers_with_details' => [
            'name' => 'Get Busy Vouchers Details',
            'group' => 'Reports',
            'path' => 'get_listofvoucherswithdetails.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['vchtype', 'Start_date', 'End_date'],
        ],
        'get_voucher_details' => [
            'name' => 'Get Voucher Details',
            'group' => 'Reports',
            'path' => 'get_voucherdetails.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['finyr', 'vchcode'],
        ],
        'get_serialwise_stock_summary' => [
            'name' => 'Get Serial Number Wise Stock Summary',
            'group' => 'Reports',
            'path' => 'get_serialwise_stock_summary.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'get_batchwise_stock_summary' => [
            'name' => 'Get Batch Wise Stock Summary',
            'group' => 'Reports',
            'path' => 'get_batchwise_stock_summary.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'get_paramwise_stock_summary' => [
            'name' => 'Get Parameter Wise Stock Summary',
            'group' => 'Reports',
            'path' => 'get_paramwise_stock_summary.php',
            'mode' => 'form',
            'write' => false,
            'params' => [],
        ],
        'get_party_wise_sale' => [
            'name' => 'Get Party Wise Sale',
            'group' => 'Reports',
            'path' => 'ab_get_cat_partywise.php',
            'mode' => 'multipart',
            'write' => false,
            'params' => ['sd', 'ed', 'ActCode'],
        ],
        'get_party_group_wise_sale' => [
            'name' => 'Get Party Group Wise Sale',
            'group' => 'Reports',
            'path' => 'ab_get_category_sale.php',
            'mode' => 'multipart',
            'write' => false,
            'params' => ['sd', 'ed', 'ActCode', 'GrpCode'],
        ],
        'get_party_group_wise_sale_alt' => [
            'name' => 'Get Party Group Wise Sale (Alt)',
            'group' => 'Reports',
            'path' => 'ab_get_categorywisesale.php',
            'mode' => 'multipart',
            'write' => false,
            'params' => ['sd', 'ed', 'ActCode', 'GrpCode'],
        ],
        'get_item_wise_sale' => [
            'name' => 'Get Item Wise Sale',
            'group' => 'Reports',
            'path' => 'ab_get_itemwisesale.php',
            'mode' => 'multipart',
            'write' => false,
            'params' => ['sd', 'ed', 'ActCode'],
        ],
        'get_item_sale_analysis' => [
            'name' => 'Get Item Sale Analysis',
            'group' => 'Reports',
            'path' => 'ab_item_sale_analysis.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['sd', 'ed'],
        ],
        'get_item_wise_pricing' => [
            'name' => 'Get Item Wise Pricing',
            'group' => 'Reports',
            'path' => 'get_ItemWisePricing.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['PriceDate'],
        ],
        'get_cancelled_voucher_list' => [
            'name' => 'Cancelled Voucher List',
            'group' => 'Reports',
            'path' => 'get_CancelledVoucherList.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['vchtype', 'Start_date', 'End_date'],
        ],
        'get_price_structure' => [
            'name' => 'Get Price Structure',
            'group' => 'Get Price Structure',
            'path' => 'get_accounts.php',
            'mode' => 'form',
            'write' => false,
            'params' => ['VchType', 'BusyMastCode', 'RecType'],
        ],
    ];

    /**
     * @return list<array{action: string, name: string, group: string, path: string, write: bool, params: list<string>}>
     */
    public static function listed(): array
    {
        $list = [];
        foreach (self::CATALOG as $action => $meta) {
            $list[] = [
                'action' => $action,
                'name' => $meta['name'],
                'group' => $meta['group'],
                'path' => $meta['path'],
                'write' => $meta['write'],
                'params' => $meta['params'],
            ];
        }

        return $list;
    }
}
