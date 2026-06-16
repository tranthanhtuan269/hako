#!/usr/bin/env bash
set -euo pipefail
cd /var/www/thuoc360
php artisan tinker --execute="
\$user = App\Models\User::where('is_admin', true)->first()
    ?? App\Models\User::whereIn('email', ['admin@viktorreview.com', 'admin@thuoc360.com'])->first();
if (! \$user) {
    \$user = new App\Models\User();
    \$user->name = 'Admin';
    \$user->is_admin = true;
}
\$user->email = 'admin@viktorreview.com';
\$user->password = bcrypt('viktorreview@');
\$user->is_admin = true;
\$user->save();
echo \$user->email . PHP_EOL;
"
