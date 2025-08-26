<?php

/**
 * KYC Examples Runner
 * 
 * This script runs all the KYC onboarding risk calculation examples
 * to demonstrate the full capabilities of the Rule Engine for
 * financial compliance and risk assessment.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "████████████████████████████████████████████████████████████████████████\n";
echo "███                    RULE ENGINE KYC EXAMPLES                     ███\n";
echo "████████████████████████████████████████████████████████████████████████\n\n";

echo "This demonstration shows how to use the Rule Engine for comprehensive\n";
echo "KYC (Know Your Customer) onboarding risk assessment in financial services.\n\n";

echo "The examples cover:\n";
echo "• Basic Risk Scoring - Customer demographics and fundamental risk factors\n";
echo "• Document Verification - ID documents, address proof, and data consistency\n";
echo "• Enhanced Due Diligence - High-value customers and complex structures\n";
echo "• Sanctions Compliance - OFAC, EU, UN sanctions and PEP screening\n";
echo "• Final Risk Assessment - Comprehensive decision making and workflow\n\n";

echo "Press Enter to start the demonstration...\n";
readline();

$examples = [
    'Basic Risk Scoring' => __DIR__ . '/BasicRiskScoring.php',
    'Document Verification' => __DIR__ . '/DocumentVerification.php',
    'Enhanced Due Diligence' => __DIR__ . '/EnhancedDueDiligence.php',
    'Sanctions Compliance' => __DIR__ . '/SanctionsCompliance.php',
    'Final Risk Assessment' => __DIR__ . '/FinalRiskAssessment.php',
];

foreach ($examples as $name => $file) {
    echo "\n" . str_repeat('█', 80) . "\n";
    echo "RUNNING: {$name}\n";
    echo str_repeat('█', 80) . "\n\n";
    
    if (file_exists($file)) {
        include $file;
    } else {
        echo "Error: Example file not found: {$file}\n";
    }
    
    echo "\nPress Enter to continue to the next example...\n";
    readline();
}

echo "\n" . str_repeat('█', 80) . "\n";
echo "███                    DEMONSTRATION COMPLETE                     ███\n";
echo str_repeat('█', 80) . "\n\n";

echo "Summary of Rule Engine KYC Capabilities Demonstrated:\n\n";

echo "1. MULTIPLE API USAGE:\n";
echo "   • NestedRuleApi: Complex business logic with nested conditions\n";
echo "   • FlatRuleAPI: Performance-critical rule evaluation\n";
echo "   • StringRuleApi: Human-readable rule expressions\n\n";

echo "2. REAL-WORLD BUSINESS SCENARIOS:\n";
echo "   • Age-based risk assessment\n";
echo "   • Income and employment verification\n";
echo "   • Geographic risk evaluation\n";
echo "   • Document quality and completeness checks\n";
echo "   • Multi-jurisdiction sanctions screening\n";
echo "   • PEP and adverse media screening\n";
echo "   • Enhanced due diligence triggers\n";
echo "   • Complex risk scoring algorithms\n\n";

echo "3. ADVANCED RULE ENGINE FEATURES:\n";
echo "   • Action-based variable modification\n";
echo "   • Conditional logic with multiple operators\n";
echo "   • Variable references and data manipulation\n";
echo "   • Complex scoring and accumulation systems\n";
echo "   • Business process automation\n";
echo "   • Risk-based decision trees\n\n";

echo "4. FINANCIAL COMPLIANCE USE CASES:\n";
echo "   • Anti-Money Laundering (AML) compliance\n";
echo "   • Know Your Customer (KYC) automation\n";
echo "   • Risk-based customer onboarding\n";
echo "   • Regulatory compliance workflows\n";
echo "   • Automated decision making with human oversight\n";
echo "   • Audit trail and documentation\n\n";

echo "These examples demonstrate how the Rule Engine can be used to build\n";
echo "sophisticated compliance and risk management systems for financial\n";
echo "institutions, fintech companies, and other regulated industries.\n\n";

echo "For more information about the Rule Engine, see the main README.md file.\n";

/**
 * This runner demonstrates:
 * 1. Complete KYC onboarding workflow automation
 * 2. Integration of multiple risk assessment components
 * 3. Real-world financial compliance scenarios
 * 4. All three Rule Engine APIs in practical use
 * 5. Complex business logic implementation
 * 6. Risk-based decision making systems
 */