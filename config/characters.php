<?php

/**
 * Central content for the dashboard's two mascot characters. Every later
 * phase (encouragement, AI mentor modes, streak/comeback messaging) should
 * pull dialogue from here instead of hardcoding lines in a controller or
 * view, so tone can be edited in one place without a deploy.
 */
return [

    'brainbot' => [
        'name'  => 'Brainbot',
        'image' => 'frontend/img/mascots/brainbot.png',
        'tone'  => 'Precise, methodical, encouraging without being soft — treats mistakes as clues to investigate, never as failures.',
        'lines' => [
            'welcome' => [
                "Ready when you are. Let's see what we're working with today.",
                "Systems online. What are we solving first?",
            ],
            'encouragement' => [
                "Good instinct. Let's check the next step.",
                "That's the right approach — keep going.",
            ],
            'mistake_review' => [
                "Interesting. Let's investigate where that went differently than expected.",
                "Not wrong so much as a clue. Let's trace it back.",
            ],
            'comeback' => [
                "Welcome back. Nothing lost — picking up right where we left off.",
                "Good to see you. Let's warm back up.",
            ],
            'milestone' => [
                "Logged. That's a real step forward.",
                "Confirmed — that skill's holding up under pressure.",
            ],
        ],
    ],

    'kea' => [
        'name'  => 'Kea',
        'image' => 'frontend/img/mascots/kea.png',
        'tone'  => 'Curious, warm, playful — encouraging without being saccharine, always genuinely interested in what the student is making or thinking.',
        'lines' => [
            'welcome' => [
                "Hey! What are we exploring today?",
                "Oh, I like where this is headed already.",
            ],
            'encouragement' => [
                "Ooh, nice — tell me more about how you got there.",
                "That's a genuinely creative way to think about it.",
            ],
            'mistake_review' => [
                "Ha, okay, that one surprised both of us. Let's poke at it together.",
                "Fair try — let's see what it was actually telling us.",
            ],
            'comeback' => [
                "You're back! No catching up needed, just pick a spot.",
                "Missed you. Ready to jump back in?",
            ],
            'milestone' => [
                "That's worth celebrating — you actually own that skill now.",
                "Look at that. You couldn't do that a few weeks ago.",
            ],
            'refresher' => [
                "It's been a little while since you practiced this one — quick refresh?",
                "This skill's been resting a bit. Let's make sure it's still solid.",
            ],
        ],
    ],

];
