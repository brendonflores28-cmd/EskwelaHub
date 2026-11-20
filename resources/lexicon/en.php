<?php

/*
| English sentiment words. Weight 1 is normal, 2 is strong.
| Words are written without apostrophes ("dont") because the analyzer removes them.
*/
return [
    'positive' => [
        'good' => 1, 'great' => 1, 'excellent' => 2, 'fast' => 1, 'quick' => 1, 'quickly' => 1, 'helpful' => 1,
        'friendly' => 1, 'kind' => 1, 'easy' => 1, 'smooth' => 1, 'clear' => 1, 'thank' => 1, 'thanks' => 1,
        'satisfied' => 1, 'happy' => 1, 'nice' => 1, 'polite' => 1, 'accommodating' => 1, 'efficient' => 1,
        'professional' => 1, 'amazing' => 2, 'wonderful' => 2, 'love' => 1, 'best' => 2, 'convenient' => 1,
        'organized' => 1, 'responsive' => 1, 'patient' => 1, 'approachable' => 1, 'perfect' => 2, 'impressed' => 1,
        'pleased' => 1, 'recommend' => 1, 'seamless' => 1, 'prompt' => 1, 'courteous' => 1, 'welcoming' => 1,
    ],
    'negative' => [
        'bad' => 1, 'slow' => 1, 'rude' => 2, 'poor' => 1, 'terrible' => 2, 'awful' => 2, 'worst' => 2,
        'difficult' => 1, 'confusing' => 1, 'long' => 1, 'delay' => 1, 'delayed' => 1, 'late' => 1,
        'unhelpful' => 2, 'disappointed' => 2, 'disappointing' => 2, 'angry' => 2, 'wrong' => 1, 'error' => 1,
        'problem' => 1, 'complicated' => 1, 'waiting' => 1, 'unprofessional' => 2, 'useless' => 2,
        'frustrating' => 2, 'hassle' => 1, 'lost' => 1, 'ignored' => 2, 'crowded' => 1, 'stressful' => 1,
        'unorganized' => 1, 'inefficient' => 1, 'unresponsive' => 2, 'hate' => 2,
    ],
    'negators' => ['not', 'no', 'never', 'cannot', 'cant', 'dont', 'didnt', 'wasnt', 'isnt', 'wont', 'hardly', 'without', 'barely', 'neither'],
    'intensifiers' => ['very', 'so', 'really', 'extremely', 'super', 'too', 'quite', 'truly'],
];
