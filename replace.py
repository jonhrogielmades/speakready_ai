import os
import re

files = [
    'resources/views/dashboard.blade.php',
    'resources/views/interview/setup.blade.php',
    'resources/views/user/drills/voice.blade.php',
    'resources/views/user/learning.blade.php',
    'resources/views/user/coach.blade.php',
    'resources/views/user/progress.blade.php',
    'resources/views/user/feedback.blade.php',
    'resources/views/user/reports.blade.php',
    'resources/views/user/leaderboard.blade.php'
]

pattern = re.compile(r'<i class="fa-solid fa-play me-1" style="color:(.+?)"></i> Replay Tutorial</button>')

for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    new_content = pattern.sub(r'<i class="fa-solid fa-play me-sm-1" style="color:\1"></i> <span class="d-none d-sm-inline">Replay Tutorial</span></button>', content)
    
    with open(f, 'w', encoding='utf-8') as file:
        file.write(new_content)

print("Replacement complete.")
