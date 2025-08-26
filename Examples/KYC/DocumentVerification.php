<?php

/**
 * Document Verification Example for KYC Onboarding
 * 
 * This example demonstrates how to use the Rule Engine for document verification
 * during the KYC process. It validates various document types, expiry dates,
 * and completeness requirements.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use JakubCiszak\RuleEngine\Api\StringRuleApi;

echo "=== KYC Document Verification Example ===\n\n";

// Sample customer document data
$customers = [
    'complete_documents' => [
        'customer_id' => 'CUST001',
        'has_passport' => true,
        'passport_expiry' => '2026-12-15',
        'has_drivers_license' => true,
        'license_expiry' => '2025-08-20',
        'has_utility_bill' => true,
        'utility_bill_date' => '2024-07-15',
        'has_bank_statement' => true,
        'bank_statement_date' => '2024-07-01',
        'address_matches' => true,
        'name_matches' => true,
        'document_quality_score' => 95,
        'verification_score' => 0,
        'verification_status' => 'pending',
        'required_additional_docs' => false,
    ],
    'expired_documents' => [
        'customer_id' => 'CUST002',
        'has_passport' => true,
        'passport_expiry' => '2023-05-10', // Expired
        'has_drivers_license' => false,
        'license_expiry' => null,
        'has_utility_bill' => true,
        'utility_bill_date' => '2024-01-15', // Too old
        'has_bank_statement' => false,
        'bank_statement_date' => null,
        'address_matches' => false,
        'name_matches' => true,
        'document_quality_score' => 70,
        'verification_score' => 0,
        'verification_status' => 'pending',
        'required_additional_docs' => false,
    ],
    'incomplete_documents' => [
        'customer_id' => 'CUST003',
        'has_passport' => false,
        'passport_expiry' => null,
        'has_drivers_license' => true,
        'license_expiry' => '2025-12-31',
        'has_utility_bill' => false,
        'utility_bill_date' => null,
        'has_bank_statement' => true,
        'bank_statement_date' => '2024-07-25',
        'address_matches' => true,
        'name_matches' => true,
        'document_quality_score' => 85,
        'verification_score' => 0,
        'verification_status' => 'pending',
        'required_additional_docs' => false,
    ],
];

/**
 * Example 1: Primary Identity Document Verification using NestedRuleApi
 * Ensures customer has valid primary identification
 */
function primaryIdVerification(array &$customer): bool
{
    $currentDate = date('Y-m-d');
    
    $rules = [
        'valid_passport' => [
            'and' => [
                ['==' => [['var' => 'has_passport'], true]],
                ['>' => [['var' => 'passport_expiry'], $currentDate]]
            ],
            'actions' => ['.verification_score + 40']
        ],
        'valid_license' => [
            'and' => [
                ['==' => [['var' => 'has_drivers_license'], true]],
                ['>' => [['var' => 'license_expiry'], $currentDate]]
            ],
            'actions' => ['.verification_score + 30']
        ],
        'expired_passport' => [
            'and' => [
                ['==' => [['var' => 'has_passport'], true]],
                ['<=' => [['var' => 'passport_expiry'], $currentDate]]
            ],
            'actions' => ['.verification_score - 20', '.required_additional_docs = true']
        ],
        'no_primary_id' => [
            'and' => [
                ['==' => [['var' => 'has_passport'], false]],
                ['==' => [['var' => 'has_drivers_license'], false]]
            ],
            'actions' => ['.verification_score - 50', '.required_additional_docs = true']
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 2: Address Verification using StringRuleApi
 * Validates proof of address documents
 */
function addressVerification(array &$customer): bool
{
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    
    $rules = [
        'recent_utility_bill' => "has_utility_bill == true and utility_bill_date > '{$threeMonthsAgo}'",
        'recent_bank_statement' => "has_bank_statement == true and bank_statement_date > '{$threeMonthsAgo}'",
        'old_utility_bill' => "has_utility_bill == true and utility_bill_date <= '{$sixMonthsAgo}'",
        'address_mismatch' => 'address_matches == false'
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['recent_utility_bill'], $customer)) {
        $customer['verification_score'] += 25;
        echo "  → Recent utility bill verified (+25 points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['recent_bank_statement'], $customer)) {
        $customer['verification_score'] += 25;
        echo "  → Recent bank statement verified (+25 points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['old_utility_bill'], $customer)) {
        $customer['verification_score'] -= 15;
        $customer['required_additional_docs'] = true;
        echo "  → Utility bill too old (-15 points, additional docs required)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['address_mismatch'], $customer)) {
        $customer['verification_score'] -= 30;
        $customer['required_additional_docs'] = true;
        echo "  → Address mismatch detected (-30 points, additional docs required)\n";
        $result = true;
    }
    
    return $result;
}

/**
 * Example 3: Document Quality Assessment using FlatRuleAPI
 * Evaluates the technical quality of submitted documents
 */
function documentQualityAssessment(array &$customer): bool
{
    $rules = [
        'rules' => [
            [
                'name' => 'excellent_quality',
                'elements' => [
                    ['type' => 'variable', 'name' => 'document_quality_score'],
                    ['type' => 'variable', 'name' => 'excellent_threshold', 'value' => 90],
                    ['type' => 'operator', 'name' => '>='],
                ],
                'actions' => ['.verification_score + 15']
            ],
            [
                'name' => 'good_quality',
                'elements' => [
                    ['type' => 'variable', 'name' => 'document_quality_score'],
                    ['type' => 'variable', 'name' => 'good_min', 'value' => 75],
                    ['type' => 'operator', 'name' => '>='],
                    ['type' => 'variable', 'name' => 'document_quality_score'],
                    ['type' => 'variable', 'name' => 'good_max', 'value' => 89],
                    ['type' => 'operator', 'name' => '<='],
                    ['type' => 'operator', 'name' => 'and'],
                ],
                'actions' => ['.verification_score + 5']
            ],
            [
                'name' => 'poor_quality',
                'elements' => [
                    ['type' => 'variable', 'name' => 'document_quality_score'],
                    ['type' => 'variable', 'name' => 'poor_threshold', 'value' => 60],
                    ['type' => 'operator', 'name' => '<'],
                ],
                'actions' => ['.verification_score - 25', '.required_additional_docs = true']
            ]
        ]
    ];

    return FlatRuleAPI::evaluate($rules, $customer);
}

/**
 * Example 4: Data Consistency Verification using NestedRuleApi
 * Checks if personal data matches across documents
 */
function dataConsistencyVerification(array &$customer): bool
{
    $rules = [
        'name_match_bonus' => [
            '==' => [['var' => 'name_matches'], true],
            'actions' => ['.verification_score + 20']
        ],
        'name_mismatch_penalty' => [
            '==' => [['var' => 'name_matches'], false],
            'actions' => ['.verification_score - 40', '.required_additional_docs = true']
        ],
        'address_match_bonus' => [
            '==' => [['var' => 'address_matches'], true],
            'actions' => ['.verification_score + 15']
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 5: Final Verification Status Determination
 */
function finalVerificationStatus(array &$customer): string
{
    $score = $customer['verification_score'];
    $needsAdditionalDocs = $customer['required_additional_docs'];
    
    if ($score >= 80 && !$needsAdditionalDocs) {
        return 'APPROVED';
    } elseif ($score >= 50 && !$needsAdditionalDocs) {
        return 'APPROVED_WITH_CONDITIONS';
    } elseif ($score >= 30 || $needsAdditionalDocs) {
        return 'ADDITIONAL_DOCUMENTS_REQUIRED';
    } else {
        return 'REJECTED';
    }
}

/**
 * Function to list missing or problematic documents
 */
function getMissingDocuments(array $customer): array
{
    $issues = [];
    $currentDate = date('Y-m-d');
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
    
    if (!$customer['has_passport'] && !$customer['has_drivers_license']) {
        $issues[] = 'Valid government-issued photo ID required';
    }
    
    if ($customer['has_passport'] && $customer['passport_expiry'] <= $currentDate) {
        $issues[] = 'Passport has expired - new passport required';
    }
    
    if ($customer['has_drivers_license'] && $customer['license_expiry'] <= $currentDate) {
        $issues[] = 'Driver\'s license has expired';
    }
    
    if (!$customer['has_utility_bill'] && !$customer['has_bank_statement']) {
        $issues[] = 'Proof of address document required (utility bill or bank statement)';
    }
    
    if ($customer['has_utility_bill'] && $customer['utility_bill_date'] <= $threeMonthsAgo) {
        $issues[] = 'Utility bill is too old - document from last 3 months required';
    }
    
    if (!$customer['address_matches']) {
        $issues[] = 'Address verification failed - documents show different addresses';
    }
    
    if (!$customer['name_matches']) {
        $issues[] = 'Name verification failed - inconsistent name across documents';
    }
    
    if ($customer['document_quality_score'] < 60) {
        $issues[] = 'Document quality too poor - clearer images/scans required';
    }
    
    return $issues;
}

// Process each customer through the document verification pipeline
foreach ($customers as $customerType => $customer) {
    echo "Processing: " . str_replace('_', ' ', ucwords($customerType)) . " (ID: {$customer['customer_id']})\n";
    echo "Document Summary:\n";
    echo "  • Passport: " . ($customer['has_passport'] ? "Yes (expires: {$customer['passport_expiry']})" : "No") . "\n";
    echo "  • Driver's License: " . ($customer['has_drivers_license'] ? "Yes (expires: {$customer['license_expiry']})" : "No") . "\n";
    echo "  • Utility Bill: " . ($customer['has_utility_bill'] ? "Yes (date: {$customer['utility_bill_date']})" : "No") . "\n";
    echo "  • Bank Statement: " . ($customer['has_bank_statement'] ? "Yes (date: {$customer['bank_statement_date']})" : "No") . "\n";
    echo "  • Quality Score: {$customer['document_quality_score']}/100\n";
    
    echo "\nVerification Results:\n";
    
    // Run all verification checks
    primaryIdVerification($customer);
    addressVerification($customer);
    documentQualityAssessment($customer);
    dataConsistencyVerification($customer);
    
    // Determine final status
    $customer['verification_status'] = finalVerificationStatus($customer);
    
    echo "  → Total Verification Score: {$customer['verification_score']}\n";
    echo "  → Verification Status: {$customer['verification_status']}\n";
    echo "  → Additional Documents Required: " . ($customer['required_additional_docs'] ? 'Yes' : 'No') . "\n";
    
    // Show any issues or missing documents
    $issues = getMissingDocuments($customer);
    if (!empty($issues)) {
        echo "  → Issues Found:\n";
        foreach ($issues as $issue) {
            echo "    - {$issue}\n";
        }
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

echo "=== Document Verification Rules Summary ===\n";
echo "Primary ID Verification:\n";
echo "  • Valid passport: +40 points\n";
echo "  • Valid driver's license: +30 points\n";
echo "  • Expired passport: -20 points, additional docs required\n";
echo "  • No primary ID: -50 points, additional docs required\n\n";

echo "Address Verification:\n";
echo "  • Recent utility bill (last 3 months): +25 points\n";
echo "  • Recent bank statement (last 3 months): +25 points\n";
echo "  • Old utility bill (>6 months): -15 points, additional docs required\n";
echo "  • Address mismatch: -30 points, additional docs required\n\n";

echo "Document Quality:\n";
echo "  • Excellent quality (90+): +15 points\n";
echo "  • Good quality (75-89): +5 points\n";
echo "  • Poor quality (<60): -25 points, additional docs required\n\n";

echo "Data Consistency:\n";
echo "  • Name matches: +20 points\n";
echo "  • Name mismatch: -40 points, additional docs required\n";
echo "  • Address matches: +15 points\n\n";

echo "Verification Status Thresholds:\n";
echo "  • APPROVED: 80+ points, no additional docs needed\n";
echo "  • APPROVED_WITH_CONDITIONS: 50+ points, no additional docs needed\n";
echo "  • ADDITIONAL_DOCUMENTS_REQUIRED: 30+ points or additional docs flagged\n";
echo "  • REJECTED: <30 points\n";

/**
 * This example demonstrates:
 * 1. Complex document verification workflows
 * 2. Date-based validations for document expiry
 * 3. Multiple verification criteria working together
 * 4. Business logic for different document types
 * 5. Scoring system with conditional requirements
 * 6. Real-world KYC document verification scenarios
 */