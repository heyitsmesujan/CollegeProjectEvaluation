<?php
// AI Feedback Generator with combined evaluation feedback
function generateAIFeedback($evaluations_by_phase) {
    if (empty($evaluations_by_phase)) return "No evaluations available yet.";
    
    $summary = "📊 **Performance Summary:**\n";
    
    foreach ($evaluations_by_phase as $evaluation_phase => $evaluations) {
        $phase_name = ucwords(str_replace('_', ' ', $evaluation_phase));
        
        // Combine all feedback for this evaluation phase
        $all_feedback = [];
        $scores = [];
        foreach ($evaluations as $evaluation) {
            if ($evaluation['feedback']) $all_feedback[] = $evaluation['feedback'];
            $scores[] = $evaluation['score'];
        }
        
        $combined_feedback = implode('. ', $all_feedback);
        $avg_score = array_sum($scores) / count($scores);
        $status = detectFeedbackStatus($combined_feedback);
        
        $summary .= $phase_name . ":\n";
        $summary .= "Status: " . $status . "\n";
        $summary .= "Feedback: " . ($combined_feedback ?: 'No feedback provided') . "\n";
        // Calculate max score based on phase
        $max_score = ($evaluation_phase === 'proposal_defense') ? 20 : 
                    (($evaluation_phase === 'midterm_defense') ? 40 : 20);
        
        $summary .= "Average Score: " . round($avg_score, 1) . "/" . $max_score . " (" . count($evaluations) . " evaluation" . (count($evaluations) > 1 ? 's' : '') . ")\n\n";
    }
    
    return trim($summary);
}

function detectFeedbackStatus($feedback) {
    if (empty($feedback)) return 'No Status Available';
    
    $feedback_lower = strtolower($feedback);
    
    // Check for improvement keywords
    $improvement_keywords = ['needs improvement', 'lacking', 'weak', 'poor', 'needs to improve', 'should improve'];
    foreach ($improvement_keywords as $keyword) {
        if (strpos($feedback_lower, $keyword) !== false) {
            return 'Improvement Required';
        }
    }
    
    // Check for positive keywords
    $positive_keywords = ['excellent', 'outstanding', 'great progress', 'solid', 'great', 'good progress', 'strong'];
    foreach ($positive_keywords as $keyword) {
        if (strpos($feedback_lower, $keyword) !== false) {
            return ucwords($keyword) . ' Progress';
        }
    }
    
    return 'Satisfactory Progress';
}

function extractFeedbackSummary($feedbacks) {
    if (empty($feedbacks)) return 'No feedback provided';
    
    $all_feedback = implode('. ', array_filter($feedbacks));
    
    // Check for status keywords first
    $status_phrases = [
        'excellent' => 'Excellent Progress',
        'outstanding' => 'Outstanding Performance', 
        'great' => 'Great Work',
        'good progress' => 'Good Progress',
        'solid' => 'Solid Performance',
        'strong' => 'Strong Performance',
        'needs improvement' => 'Improvement Required',
        'lacking' => 'Improvement Required',
        'weak' => 'Improvement Required',
        'poor' => 'Improvement Required'
    ];
    
    foreach ($status_phrases as $phrase => $status) {
        if (stripos($all_feedback, $phrase) !== false) {
            // Return status + truncated feedback
            $truncated = strlen($all_feedback) > 30 ? substr($all_feedback, 0, 30) . '...' : $all_feedback;
            return $status . ' - ' . $truncated;
        }
    }
    
    // Return truncated feedback if no status keywords found
    if (strlen($all_feedback) > 50) {
        return substr($all_feedback, 0, 50) . '...';
    }
    
    return $all_feedback;
}

// Function to get AI evaluation for student portal
function getAIEvaluation($project_id, $pdo) {
    // Get project evaluations grouped by phase
    $stmt = $pdo->prepare("SELECT evaluation_phase, score, feedback FROM evaluations_phases WHERE project_id = ? ORDER BY evaluation_date ASC");
    $stmt->execute([$project_id]);
    $evaluations = $stmt->fetchAll();
    
    if (empty($evaluations)) {
        return "📊 **Performance Summary:**\n• No evaluations available yet";
    }
    
    // Group evaluations by phase
    $evaluations_by_phase = [];
    foreach ($evaluations as $evaluation) {
        $evaluations_by_phase[$evaluation['evaluation_phase']][] = $evaluation;
    }
    
    return generateAIFeedback($evaluations_by_phase);
}
?>