<?php

class CRM_Reportorganizer_Utils {

  public static function sortArrayByArray(array $toSort, array $sortByValuesAsKeys) {
    $commonKeysInOrder = array_intersect_key(array_flip($sortByValuesAsKeys), $toSort);
    $commonKeysWithValue = array_intersect_key($toSort, $commonKeysInOrder);
    $sorted = array_merge($commonKeysInOrder, $commonKeysWithValue);
    return $sorted;
  }

  public static function noAccordionSorter($component, $sortOrder, $rows) {
    $sortedSections = [];
    foreach ($sortOrder as $order) {
      foreach ($rows[$component]['no_accordion'] as $k => $v) {
        if ($order == $v['title']) {
          $sortedSections[$k] = $rows[$component]['no_accordion'][$k];
        }
      }
    }
    return $sortedSections;
  }

  public static function accordionSorter($component, $sortOrder, $rows) {
    $sortedSections = [];
    foreach ($sortOrder as $order) {
      if (array_key_exists($order, $rows[$component]['accordion'])) {
        $sortedSections[$order] = $rows[$component]['accordion'][$order];
      }
    }
    return $sortedSections;
  }

  public static function insideAccordionSorter($component, $section, $sortOrder, $rows) {
    $sortedSections = [];
    foreach ($sortOrder as $order) {
      foreach ($rows[$component]['accordion'][$section] as $k => $v) {
        if ($order == $v['title']) {
          $sortedSections[$k] = $rows[$component]['accordion'][$section][$k];
        }
      }
    }
    return $sortedSections;
  }

  public static function updateReportTemplates($componentId) {
    $templates = civicrm_api3('ReportTemplate', 'get', [
      'sequential' => 1,
      'component_id' => ['IS NULL' => 1],
      'options' => ['limit' => 0],
    ]);
    if (!empty($templates['values'])) {
      foreach ($templates['values'] as $template) {
        civicrm_api3('ReportTemplate', 'create', [
          'option_group_id' => "report_template",
          'component_id' => $componentId,
          'id' => $template['id'],
        ]);
      }
    }
  }

  public static function renameReportInstances() {
    $reportInstances = [
      // Contact Reports
      "Contact Report (Detailed)" => "All Contacts",
      "Activity Report" => "All Activities excluding Contributions",
      "New Email Replies" => "All new email Replies",
      "Relationship Report" => "All Relationships between Contacts",
      // Contribution Reports
      "Contribution History by Campaign (Summary)" => "Total amounts raised by Campaign",
      "Contribution History by Campaign (Detailed)" => "Total amounts raised by Campaign with individual Contribution information",
      "Contribution History by Campaign (Monthly)" => "Total amounts raised by Campaign month over month",
      "Contribution History by Campaign (Yearly)" => "Total amounts raised by Campaign year over year",
      "Contribution History by Campaign Group (Summary)" => "Total amounts raised by Campaign Group",
      "Contribution History by Campaign Group (Detailed)" => "Total amounts raised by Campaign Group with individual Contribution information",
      "Contribution History by CH Fund (Summary)" => "Total amounts raised by CanadaHelps Fund",
      "Contribution History by Fund (Summary)" => "Total amounts raised by Fund",
      "Contribution History by Fund (Detailed)" => "Total amounts raised by Fund with individual Contribution information",
      "Contribution History by Fund (Monthly)" => "Total amounts raised by Fund month over month",
      "Contribution History by Fund (Yearly)" => "Total amounts raised by Fund year over year",
      "Contribution History by GL Account (Summary)" => "Total amounts raised by GL Account",
      "Contribution History by GL Account (Detailed)" => "Total amounts raised by GL Account with individual Contribution information",
      "Contribution History by Source (Summary)" => "Total amounts raised by Source",
      "Recurring Contributions (Summary)" => "Total amounts raised by Recurring Contributions with individual Contribution information",
      "Receipts" => "Contributions by Receipt Number",
      // Opportunity Reports
      "Opportunity Report" => "All Opportunities",
    ];
    foreach ($reportInstances as $title => $description) {
      $instance = civicrm_api3("ReportInstance", "get", [
        "sequential" => 1,
        "title" => $title,
      ]);
      if (!empty($instance['values'])) {
        civicrm_api3("ReportInstance", "create", [
          "title" => $title,
          "report_id" => $instance['values'][0]["report_id"],
          "description" => $description,
          "id" => $instance['values'][0]["id"],
        ]);
      }
    }
  }

  public static function renameReportTemplates() {
    $reportTemplates = [
      // Contact Report Templates
      "Constituent Report (Summary)" => [
        "label" => "Contacts (Summary)",
        "description" => "All Contacts",
      ],
      "Constituent Report (Detail)" => [
        "label" => "Contacts (Detailled)",
        "description" => "All Contacts with extra fields",
      ],
      "Extended Report - Flexible contact report" => [
        "label" => "Contacts (Detailed)",
        "description" => "All Contacts with latest Activity information",
      ],
      "Extended Report - Pivot data contact report" => [
        "label" => "Contacts (Extended, Pivot Chart)",
        "description" => "All Contacts with extra fields, in a pivot chart",
      ],
      "Database Log Report" => [
        "label" => "Database Log",
        "description" => "Log of DMS User actions",
      ],
      "Address History" => [
        "label" => "Address History",
        "description" => "Log of Contact Address History",
      ],
      // Contribution Report Templates
      "Contribution Summary Report" => [
        "label" => "Contributions (Summary)",
        "description" => "Total amounts raised",
      ],
      "Contribution Detail Report" => [
        "label" => "Contributions (Detailled)",
        "description" => "Total amounts raised with individual Contribution information",
      ],
      "Repeat Contributions Report" => [
        "label" => "Repeat Contributions",
        "description" => "Total amounts raised from repeat Contributions",
      ],
      "Top Donors Report" => [
        "label" => "Top Donors",
        "description" => "Top Donors for a defined date range",
      ],
      "SYBUNT Report" => [
        "label" => "SYBUNT",
        "description" => "Total amounts raised from Some Years But Not This Year",
      ],
      "LYBNT Report" => [
        "label" => "LYBNT",
        "description" => "Total amounts raised from Last Year But Not This Year",
      ],
      "Contributions by Organization Report" => [
        "label" => "Contributions by Organization",
        "description" => "Total amounts raised grouped by Organization with individual Contribution information",
      ],
      "Contributions by Household Report" => [
        "label" => "Contributions by Household",
        "description" => "Total amounts raised grouped by Households with individual Contribution information",
      ],
      "Contribution Aggregate by Relationship" => [
        "label" => "Contributions by Relationship",
        "description" => "Total amounts raised grouped by Relationships with individual Contribution information",
      ],
      "Bookkeeping Transactions Report" => [
        "label" => "Contributions for Bookkeeping",
        "description" => "Total amounts raised with bookkeeping transactions information",
      ],
      "Extended Report - Contributions Overview" => [
        "label" => "Contributions (Extended, Summary)",
        "description" => "Total amounts raised in summaries",
      ],
      "Extended Report - Contributions" => [
        "label" => "Contributions (Detailed)",
        "description" => "Total amounts raised with individual Contribution information",
      ],
      "Extended Report - Contribution Pivot Chart" => [
        "label" => "Contributions (Extended, Pivot Chart)",
        "description" => "Total amounts raised with extra fields, in a pivot chart",
      ],
      "Extended Report - Contributions Detail with extra fields" => [
        "label" => "Contributions (Extended, Extra Fields)",
        "description" => "Total amounts raised with extra fields",
      ],
      "Extended Report - Bookkeeping with extra fields" => [
        "label" => "Contributions for Bookkeeping (Detailed)",
        "description" => "Total amounts raised with bookkeeping transactions information",
      ],
      // Recurring Contribution Templates
      "Recurring Contributions Summary" => [
        "label" => "Recurring Contributions (Summary)",
        "description" => "Total amounts raised from Recurring Contributions by each Payment Method",
      ],
      "Recurring Contributions Report" => [
        "label" => "Recurring Contributions (Detailled)",
        "description" => "Total amounts raised for Recurring Contributions",
      ],
      "Extended Report - Recurring Contribution Pivot Chart" => [
        "label" => "Recurring Contributions (Extended, Pivot Chart)",
        "description" => "Total amounts raised for Recurring Contributions with extra fields, in a pivot chart",
      ],
      "Extended Report - Recurring Contributions" => [
        "label" => "Recurring Contributions (Detailed)",
        "description" => "Total amounts raised from Recurring Contributions with individual Contribution information",
      ],
      // Receipt Reports
      "Tax Receipts - Receipts Issued" => [
        "label" => "Tax Receipts (Issued)",
        "description" => "All Tax Receipts already Issued",
      ],
      "Tax Receipts - Receipts Not Issued" => [
        "label" => "Tax Receipts (Not Yet Issued)",
        "description" => "All Tax Receipts not yet Issued",
      ],
      // Activity Reports
      "Activity Summary Report" => [
        "label" => "Activities (Summary)",
        "description" => "All Activities by type and date information",
      ],
      "Activity Details Report" => [
        "label" => "Activities (Detailled)",
        "description" => "All Activities",
      ],
      "Extended Report - Activities" => [
        "label" => "Activities (Extended)",
        "description" => "All Activities with extra fields",
      ],
      "Extended Report - Activity Pivot Chart" => [
        "label" => "Activities (Extended, Pivot Chart)",
        "description" => "All Activities with extra fields, in a pivot chart",
      ],
      "Extended Report - Editable Activities" => [
        "label" => "Activities (Detailed)",
        "description" => "All Activities with detailed information",
      ],
      // Relationship Reports
      "Relationship Report" => [
        "label" => "Relationships",
        "description" => "All Relationships",
      ],
      "Current Employer Report" => [
        "label" => "Current Employer",
        "description" => "All Employer/Employee Relationships",
      ],
      "Extended Report - Relationships" => [
        "label" => "Relationships (Detailed)",
        "description" => "All Relationships with extra fields",
      ],
      // Mailing Reports
      "Mail Bounce Report" => [
        "label" => "Mail Bounces",
        "description" => "All Mailings with Bounce activity",
      ],
      "Mail Summary Report" => [
        "label" => "Mail (Summary)",
        "description" => "All DMS Mailings statistics",
      ],
      "Mail Opened Report" => [
        "label" => "Mail Opened",
        "description" => "All Contacts who opened emails from a Mailing",
      ],
      "Mail Click-Through Report" => [
        "label" => "Mail Click-Through",
        "description" => "All Mailings and Clicks Tracking",
      ],
      "Mail Detail Report" => [
        "label" => "Mail (Detailed)",
        "description" => "All Mailings with detailed information",
      ],
      // Opportunity Reports
      "Opportunity Report (Detail)" => [
        "label" => "Opportunity Report (Detailled)",
        "description" => "All Opportunities",
      ],
      "Opportunity Report (Statistics)" => [
        "label" => "Opportunity Report (Statistics)",
        "description" => "All Opportunities in summaries",
      ],
      "Extended Report - Opportunity Detail" => [
        "label" => "Opportunity (Detailed)",
        "description" => "All Opportunities with detailed information",
      ],
    ];
    foreach ($reportTemplates as $label => $change) {
      $template = civicrm_api3("ReportTemplate", "get", [
        "sequential" => 1,
        "label" => $label,
      ]);
      if (!empty($template['values'])) {
        civicrm_api3("ReportTemplate", "create", [
          "label" => $change['label'],
          "option_group_id" => "report_template",
          "description" => $change['description'],
          "id" => $template['values'][0]["id"],
        ]);
      }
    }
  }
}

