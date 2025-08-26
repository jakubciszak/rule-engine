<?php

/**
 * Final Risk Assessment Example for KYC Onboarding
 * 
 * This example demonstrates how to use the Rule Engine to combine multiple risk factors
 * from basic scoring, document verification, enhanced due diligence, and sanctions screening
 * into a comprehensive final risk assessment and onboarding decision.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use JakubCiszak\RuleEngine\Api\StringRuleApi;

echo "=== KYC Final Risk Assessment Example ===\n\n";

// Comprehensive customer data combining all assessment areas
$customers = [
    'low_risk_approved' => [
        'customer_id' => 'LOW001',
        'customer_name' => 'Sarah Johnson',
        
        // Basic Risk Factors
        'basic_risk_score' => 15,
        'age' => 32,
        'income_risk_level' => 'low',
        'geographic_risk_level' => 'low',
        'employment_risk_level' => 'low',
        
        // Document Verification
        'document_verification_score' => 85,
        'document_verification_status' => 'approved',
        'documents_complete' => true,
        'document_quality_adequate' => true,
        
        // Enhanced Due Diligence
        'edd_required' => false,
        'edd_score' => 10,
        'is_high_value_customer' => false,
        'complex_source_of_funds' => false,
        
        // Sanctions and Compliance
        'sanctions_screening_clear' => true,
        'compliance_score' => 5,
        'manual_review_required' => false,
        'compliance_alerts_count' => 0,
        
        // Final Assessment Fields
        'final_risk_score' => 0,
        'final_risk_level' => 'pending',
        'onboarding_decision' => 'pending',
        'monitoring_level' => 'standard',
        'approval_level_required' => 'none',
        'conditions' => [],
        'next_review_date' => null,
    ],
    
    'medium_risk_conditional' => [
        'customer_id' => 'MED001',
        'customer_name' => 'Carlos Rodriguez',
        
        // Basic Risk Factors
        'basic_risk_score' => 45,
        'age' => 28,
        'income_risk_level' => 'medium',
        'geographic_risk_level' => 'medium',
        'employment_risk_level' => 'medium',
        
        // Document Verification
        'document_verification_score' => 70,
        'document_verification_status' => 'approved_with_conditions',
        'documents_complete' => true,
        'document_quality_adequate' => true,
        
        // Enhanced Due Diligence
        'edd_required' => true,
        'edd_score' => 55,
        'is_high_value_customer' => true,
        'complex_source_of_funds' => true,
        
        // Sanctions and Compliance
        'sanctions_screening_clear' => true,
        'compliance_score' => 25,
        'manual_review_required' => true,
        'compliance_alerts_count' => 2,
        
        // Final Assessment Fields
        'final_risk_score' => 0,
        'final_risk_level' => 'pending',
        'onboarding_decision' => 'pending',
        'monitoring_level' => 'standard',
        'approval_level_required' => 'none',
        'conditions' => [],
        'next_review_date' => null,
    ],
    
    'high_risk_rejected' => [
        'customer_id' => 'HIGH001',
        'customer_name' => 'Viktor Petrov',
        
        // Basic Risk Factors
        'basic_risk_score' => 85,
        'age' => 45,
        'income_risk_level' => 'high',
        'geographic_risk_level' => 'high',
        'employment_risk_level' => 'high',
        
        // Document Verification
        'document_verification_score' => 40,
        'document_verification_status' => 'additional_documents_required',
        'documents_complete' => false,
        'document_quality_adequate' => false,
        
        // Enhanced Due Diligence
        'edd_required' => true,
        'edd_score' => 95,
        'is_high_value_customer' => true,
        'complex_source_of_funds' => true,
        
        // Sanctions and Compliance
        'sanctions_screening_clear' => false,
        'compliance_score' => 75,
        'manual_review_required' => true,
        'compliance_alerts_count' => 5,
        
        // Final Assessment Fields
        'final_risk_score' => 0,
        'final_risk_level' => 'pending',
        'onboarding_decision' => 'pending',
        'monitoring_level' => 'standard',
        'approval_level_required' => 'none',
        'conditions' => [],
        'next_review_date' => null,
    ],
];

/**
 * Example 1: Basic Risk Score Integration using NestedRuleApi
 * Incorporates fundamental risk factors into the final assessment
 */
function integrateBasicRiskFactors(array &$customer): bool
{
    $rules = [
        'basic_risk_contribution' => [
            '>' => [['var' => 'basic_risk_score'], 0],
            'actions' => ['.final_risk_score + .basic_risk_score']
        ],
        'high_basic_risk_penalty' => [
            '>=' => [['var' => 'basic_risk_score'], 70],
            'actions' => [
                '.final_risk_score + 20',
                '.conditions + ENHANCED_MONITORING_REQUIRED'
            ]
        ],
        'age_employment_mismatch' => [
            'and' => [
                ['==' => [['var' => 'employment_risk_level'], 'high']],
                ['<' => [['var' => 'age'], 25]]
            ],
            'actions' => [
                '.final_risk_score + 15',
                '.conditions + EMPLOYMENT_VERIFICATION_REQUIRED'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 2: Document Verification Impact using StringRuleApi
 * Assesses how document verification results affect final risk
 */
function integrateDocumentVerification(array &$customer): bool
{
    $rules = [
        'document_verification_bonus' => 'document_verification_score >= 80 and documents_complete == true',
        'document_quality_issue' => 'document_quality_adequate == false',
        'incomplete_documents' => 'documents_complete == false',
        'low_verification_score' => 'document_verification_score < 50'
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['document_verification_bonus'], $customer)) {
        $customer['final_risk_score'] -= 10; // Reduce risk for good documentation
        echo "  → Excellent document verification (-10 risk points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['document_quality_issue'], $customer)) {
        $customer['final_risk_score'] += 25;
        $customer['conditions'][] = 'IMPROVED_DOCUMENTATION_REQUIRED';
        echo "  → Document quality issues (+25 risk points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['incomplete_documents'], $customer)) {
        $customer['final_risk_score'] += 30;
        $customer['conditions'][] = 'COMPLETE_DOCUMENTATION_REQUIRED';
        echo "  → Incomplete documentation (+30 risk points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['low_verification_score'], $customer)) {
        $customer['final_risk_score'] += 40;
        $customer['approval_level_required'] = 'senior_compliance';
        echo "  → Low document verification score (+40 risk points)\n";
        $result = true;
    }
    
    return $result;
}

/**
 * Example 3: Enhanced Due Diligence Integration using FlatRuleAPI
 * Incorporates EDD requirements and findings into final risk assessment
 */
function integrateEnhancedDueDiligence(array &$customer): bool
{
    $rules = [
        'rules' => [
            [
                'name' => 'edd_required_penalty',
                'elements' => [
                    ['type' => 'variable', 'name' => 'edd_required'],
                    ['type' => 'variable', 'name' => 'true_value', 'value' => true],
                    ['type' => 'operator', 'name' => '=='],
                ],
                'actions' => [
                    '.final_risk_score + 25',
                    '.approval_level_required = compliance_officer',
                    '.conditions + EDD_COMPLETION_REQUIRED'
                ]
            ],
            [
                'name' => 'high_edd_score',
                'elements' => [
                    ['type' => 'variable', 'name' => 'edd_score'],
                    ['type' => 'variable', 'name' => 'high_threshold', 'value' => 70],
                    ['type' => 'operator', 'name' => '>='],
                ],
                'actions' => [
                    '.final_risk_score + 35',
                    '.approval_level_required = senior_management',
                    '.monitoring_level = intensive'
                ]
            ],
            [
                'name' => 'complex_high_value',
                'elements' => [
                    ['type' => 'variable', 'name' => 'is_high_value_customer'],
                    ['type' => 'variable', 'name' => 'true_value', 'value' => true],
                    ['type' => 'operator', 'name' => '=='],
                    ['type' => 'variable', 'name' => 'complex_source_of_funds'],
                    ['type' => 'variable', 'name' => 'true_value', 'value' => true],
                    ['type' => 'operator', 'name' => '=='],
                    ['type' => 'operator', 'name' => 'and'],
                ],
                'actions' => [
                    '.final_risk_score + 20',
                    '.conditions + ONGOING_SOURCE_MONITORING'
                ]
            ]
        ]
    ];

    return FlatRuleAPI::evaluate($rules, $customer);
}

/**
 * Example 4: Sanctions and Compliance Integration using NestedRuleApi
 * Incorporates sanctions screening and compliance findings
 */
function integrateSanctionsCompliance(array &$customer): bool
{
    $rules = [
        'sanctions_failure' => [
            '==' => [['var' => 'sanctions_screening_clear'], false],
            'actions' => [
                '.final_risk_score + 100',
                '.onboarding_decision = blocked',
                '.conditions + SANCTIONS_CLEARANCE_REQUIRED'
            ]
        ],
        'high_compliance_score' => [
            '>=' => [['var' => 'compliance_score'], 50],
            'actions' => [
                '.final_risk_score + .compliance_score',
                '.approval_level_required = senior_compliance'
            ]
        ],
        'multiple_compliance_alerts' => [
            '>=' => [['var' => 'compliance_alerts_count'], 3],
            'actions' => [
                '.final_risk_score + 30',
                '.manual_review_required = true',
                '.conditions + DETAILED_COMPLIANCE_REVIEW'
            ]
        ],
        'manual_review_triggered' => [
            '==' => [['var' => 'manual_review_required'], true],
            'actions' => [
                '.final_risk_score + 15',
                '.conditions + MANUAL_COMPLIANCE_REVIEW'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 5: Final Risk Level Determination using StringRuleApi
 * Determines the overall risk level based on combined factors
 */
function determineFinalRiskLevel(array &$customer): bool
{
    $rules = [
        'critical_risk' => 'final_risk_score >= 150',
        'high_risk' => 'final_risk_score >= 100 and final_risk_score < 150',
        'medium_risk' => 'final_risk_score >= 50 and final_risk_score < 100',
        'low_risk' => 'final_risk_score < 50'
    ];

    if (StringRuleApi::evaluate($rules['critical_risk'], $customer)) {
        $customer['final_risk_level'] = 'CRITICAL';
        $customer['monitoring_level'] = 'intensive';
        return true;
    } elseif (StringRuleApi::evaluate($rules['high_risk'], $customer)) {
        $customer['final_risk_level'] = 'HIGH';
        $customer['monitoring_level'] = 'enhanced';
        return true;
    } elseif (StringRuleApi::evaluate($rules['medium_risk'], $customer)) {
        $customer['final_risk_level'] = 'MEDIUM';
        $customer['monitoring_level'] = 'enhanced';
        return true;
    } elseif (StringRuleApi::evaluate($rules['low_risk'], $customer)) {
        $customer['final_risk_level'] = 'LOW';
        $customer['monitoring_level'] = 'standard';
        return true;
    }
    
    return false;
}

/**
 * Example 6: Final Onboarding Decision using NestedRuleApi
 * Makes the final onboarding decision based on all factors
 */
function makeFinalOnboardingDecision(array &$customer): bool
{
    if ($customer['onboarding_decision'] === 'blocked') {
        return true; // Already blocked by sanctions
    }
    
    $rules = [
        'auto_approve' => [
            'and' => [
                ['==' => [['var' => 'final_risk_level'], 'LOW']],
                ['<=' => [['var' => 'final_risk_score'], 30]],
                ['==' => [['var' => 'sanctions_screening_clear'], true]],
                ['==' => [['var' => 'edd_required'], false]]
            ],
            'actions' => ['.onboarding_decision = approved']
        ],
        'conditional_approval' => [
            'and' => [
                ['in' => [['var' => 'final_risk_level'], ['LOW', 'MEDIUM']]],
                ['<=' => [['var' => 'final_risk_score'], 80]],
                ['==' => [['var' => 'sanctions_screening_clear'], true]]
            ],
            'actions' => ['.onboarding_decision = approved_with_conditions']
        ],
        'manual_review_required' => [
            'and' => [
                ['in' => [['var' => 'final_risk_level'], ['MEDIUM', 'HIGH']]],
                ['<=' => [['var' => 'final_risk_score'], 120]],
                ['==' => [['var' => 'sanctions_screening_clear'], true]]
            ],
            'actions' => ['.onboarding_decision = manual_review_required']
        ],
        'reject_application' => [
            'or' => [
                ['==' => [['var' => 'final_risk_level'], 'CRITICAL'],
                ['>' => [['var' => 'final_risk_score'], 120]],
                ['==' => [['var' => 'sanctions_screening_clear'], false]]
            ],
            'actions' => ['.onboarding_decision = rejected']
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Function to set review dates based on risk level and monitoring requirements
 */
function setReviewSchedule(array &$customer): void
{
    $reviewIntervals = [
        'intensive' => '+1 month',
        'enhanced' => '+3 months',
        'standard' => '+12 months'
    ];
    
    $interval = $reviewIntervals[$customer['monitoring_level']] ?? '+12 months';
    $customer['next_review_date'] = date('Y-m-d', strtotime($interval));
}

/**
 * Function to get onboarding recommendations based on decision
 */
function getOnboardingRecommendations(array $customer): array
{
    $recommendations = [];
    
    switch ($customer['onboarding_decision']) {
        case 'approved':
            $recommendations[] = 'Customer approved for standard onboarding';
            $recommendations[] = "Apply {$customer['monitoring_level']} monitoring level";
            $recommendations[] = "Next review scheduled for {$customer['next_review_date']}";
            break;
            
        case 'approved_with_conditions':
            $recommendations[] = 'Customer approved with conditions';
            $recommendations[] = 'Complete all required conditions before account activation';
            $recommendations[] = "Apply {$customer['monitoring_level']} monitoring level";
            $recommendations[] = "Next review scheduled for {$customer['next_review_date']}";
            break;
            
        case 'manual_review_required':
            $recommendations[] = 'Manual review required before final decision';
            $recommendations[] = "Escalate to {$customer['approval_level_required']} level";
            $recommendations[] = 'Complete enhanced due diligence if not already done';
            $recommendations[] = 'Review all compliance alerts and risk factors';
            break;
            
        case 'rejected':
            $recommendations[] = 'Application rejected - do not proceed with onboarding';
            $recommendations[] = 'Document rejection reason in customer file';
            $recommendations[] = 'Consider filing SAR if suspicious activity detected';
            break;
            
        case 'blocked':
            $recommendations[] = 'Customer blocked - immediate escalation required';
            $recommendations[] = 'Do not proceed under any circumstances';
            $recommendations[] = 'File Suspicious Activity Report (SAR)';
            $recommendations[] = 'Notify senior compliance management';
            break;
    }
    
    return $recommendations;
}

/**
 * Function to format conditions for display
 */
function formatConditions(array $conditions): array
{
    $conditionMap = [
        'ENHANCED_MONITORING_REQUIRED' => 'Enhanced transaction monitoring required',
        'EMPLOYMENT_VERIFICATION_REQUIRED' => 'Additional employment verification required',
        'IMPROVED_DOCUMENTATION_REQUIRED' => 'Higher quality documentation required',
        'COMPLETE_DOCUMENTATION_REQUIRED' => 'Complete all missing documentation',
        'EDD_COMPLETION_REQUIRED' => 'Complete Enhanced Due Diligence process',
        'ONGOING_SOURCE_MONITORING' => 'Ongoing source of funds monitoring required',
        'SANCTIONS_CLEARANCE_REQUIRED' => 'Sanctions clearance required before proceeding',
        'DETAILED_COMPLIANCE_REVIEW' => 'Detailed compliance review required',
        'MANUAL_COMPLIANCE_REVIEW' => 'Manual compliance officer review required'
    ];
    
    return array_map(function($condition) use ($conditionMap) {
        return $conditionMap[$condition] ?? $condition;
    }, array_unique($conditions));
}

// Process each customer through the comprehensive final risk assessment
foreach ($customers as $customerType => $customer) {
    echo "Processing: " . str_replace('_', ' ', ucwords($customerType)) . " (ID: {$customer['customer_id']})\n";
    echo "Customer: {$customer['customer_name']}\n";
    echo "\nInput Risk Factors:\n";
    echo "  • Basic Risk Score: {$customer['basic_risk_score']}\n";
    echo "  • Document Verification Score: {$customer['document_verification_score']}\n";
    echo "  • EDD Required: " . ($customer['edd_required'] ? 'Yes' : 'No') . " (Score: {$customer['edd_score']})\n";
    echo "  • Sanctions Clear: " . ($customer['sanctions_screening_clear'] ? 'Yes' : 'No') . " (Compliance Score: {$customer['compliance_score']})\n";
    echo "  • Compliance Alerts: {$customer['compliance_alerts_count']}\n";
    
    echo "\nFinal Risk Assessment Process:\n";
    
    // Run comprehensive risk assessment
    integrateBasicRiskFactors($customer);
    integrateDocumentVerification($customer);
    integrateEnhancedDueDiligence($customer);
    integrateSanctionsCompliance($customer);
    determineFinalRiskLevel($customer);
    makeFinalOnboardingDecision($customer);
    setReviewSchedule($customer);
    
    // Get recommendations and format conditions
    $recommendations = getOnboardingRecommendations($customer);
    $formattedConditions = formatConditions($customer['conditions']);
    
    echo "\nFinal Assessment Results:\n";
    echo "  → Final Risk Score: {$customer['final_risk_score']}\n";
    echo "  → Risk Level: {$customer['final_risk_level']}\n";
    echo "  → Onboarding Decision: " . strtoupper(str_replace('_', ' ', $customer['onboarding_decision'])) . "\n";
    echo "  → Monitoring Level: {$customer['monitoring_level']}\n";
    echo "  → Approval Required: {$customer['approval_level_required']}\n";
    
    if (!empty($formattedConditions)) {
        echo "  → Conditions:\n";
        foreach ($formattedConditions as $condition) {
            echo "    - {$condition}\n";
        }
    }
    
    if ($customer['next_review_date']) {
        echo "  → Next Review Date: {$customer['next_review_date']}\n";
    }
    
    echo "\nRecommendations:\n";
    foreach ($recommendations as $recommendation) {
        echo "  - {$recommendation}\n";
    }
    
    echo "\n" . str_repeat('=', 70) . "\n\n";
}

echo "=== Final Risk Assessment Rules Summary ===\n";
echo "Risk Score Calculation:\n";
echo "  • Base: Basic Risk Score + Document Issues + EDD Score + Compliance Score\n";
echo "  • Adjustments: Document quality (-10 to +40), EDD requirements (+25 to +35)\n";
echo "  • Penalties: Sanctions issues (+100), Multiple alerts (+30)\n\n";

echo "Risk Level Thresholds:\n";
echo "  • LOW: <50 points\n";
echo "  • MEDIUM: 50-99 points\n";
echo "  • HIGH: 100-149 points\n";
echo "  • CRITICAL: 150+ points\n\n";

echo "Onboarding Decisions:\n";
echo "  • APPROVED: Low risk, <30 points, sanctions clear, no EDD\n";
echo "  • APPROVED_WITH_CONDITIONS: Low/Medium risk, <80 points, sanctions clear\n";
echo "  • MANUAL_REVIEW_REQUIRED: Medium/High risk, <120 points, sanctions clear\n";
echo "  • REJECTED: Critical risk, >120 points, or sanctions failure\n";
echo "  • BLOCKED: Immediate sanctions or compliance block\n\n";

echo "Monitoring Levels:\n";
echo "  • STANDARD: Annual reviews\n";
echo "  • ENHANCED: Quarterly reviews\n";
echo "  • INTENSIVE: Monthly reviews\n\n";

echo "Approval Levels:\n";
echo "  • NONE: System approval\n";
echo "  • COMPLIANCE_OFFICER: Compliance team approval\n";
echo "  • SENIOR_COMPLIANCE: Senior compliance approval\n";
echo "  • SENIOR_MANAGEMENT: Executive approval\n";

/**
 * This comprehensive example demonstrates:
 * 1. Integration of multiple risk assessment components
 * 2. Complex decision trees with multiple criteria
 * 3. Risk-based monitoring and review schedules
 * 4. Conditional approval workflows
 * 5. Escalation and approval hierarchies
 * 6. Real-world KYC onboarding scenarios
 * 7. Comprehensive rule-based decision making
 * 8. Business process automation with human oversight
 */