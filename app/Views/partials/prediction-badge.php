<?php if (empty($prediction)): ?>
    <span class="prediction-unavailable">Prediction unavailable</span>
<?php else: ?>
    <div class="prediction-compact">
        <span class="signal <?= e(strtolower((string) $prediction['signal'])) ?>"><?= e(strtoupper((string) $prediction['signal'])) ?></span>
        <strong>Estimated <?= e(number_format((float) $prediction['estimated_probability_up'], 1)) ?>% up</strong>
        <span><?= e($prediction['horizon']) ?> horizon · <?= e(number_format((float) $prediction['confidence_score'], 1)) ?>% confidence · <?= e(ucfirst((string) $prediction['risk_level'])) ?> risk</span>
    </div>
<?php endif; ?>

