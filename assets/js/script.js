function addCriteria() {
    const container = document.getElementById('criteria-container');
    const criteriaItem = document.createElement('div');
    criteriaItem.className = 'criteria-item';
    criteriaItem.innerHTML = `
        <div class="grid grid-3">
            <div class="form-group">
                <label>Criteria Name</label>
                <input type="text" name="criteria_name[]" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Max Score</label>
                <input type="number" name="criteria_score[]" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="criteria_desc[]" class="form-control">
            </div>
        </div>
    `;
    container.appendChild(criteriaItem);
}

function loadRubric(rubricId) {
    const select = document.querySelector('select[name="rubric_id"]');
    const option = select.querySelector(`option[value="${rubricId}"]`);
    
    if (!option) return;
    
    const criteria = JSON.parse(option.dataset.criteria);
    const container = document.getElementById('evaluation-criteria');
    
    let html = '<h3>Evaluation Criteria</h3>';
    let maxTotal = 0;
    
    criteria.forEach((criterion, index) => {
        maxTotal += criterion.max_score;
        html += `
            <div class="card">
                <h4>${criterion.name}</h4>
                <p>${criterion.description}</p>
                <div class="form-group">
                    <label>Score (0 - ${criterion.max_score})</label>
                    <input type="number" name="scores[${index}]" class="form-control" min="0" max="${criterion.max_score}" required>
                </div>
            </div>
        `;
    });
    
    html += `<input type="hidden" name="max_total" value="${maxTotal}">`;
    container.innerHTML = html;
}

// AI Feedback Assistant (Mock implementation)
function generateAIFeedback(projectData, rubricData) {
    // This would integrate with Gemini API in a real implementation
    const suggestions = [
        "Consider providing more specific examples in the project documentation.",
        "The implementation shows good understanding of core concepts.",
        "Recommend improving code organization and commenting.",
        "Strong analytical approach demonstrated throughout the project."
    ];
    
    return suggestions[Math.floor(Math.random() * suggestions.length)];
}