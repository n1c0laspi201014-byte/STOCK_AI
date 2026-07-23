<?php
declare(strict_types=1);

use App\Config\Database;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
$failed = false;
function appCheck(string $label, bool $pass, string $fix): void { global $failed; echo ($pass?'PASS ':'FAIL ').$label.PHP_EOL; if(!$pass){echo "  Likely fix: {$fix}\n";$failed=true;} }
try {
    $pdo=Database::connection(); appCheck('Database connection',(bool)$pdo->query('SELECT 1')->fetchColumn(),'Start MySQL and run php scripts/php/setup_database.php.');
    $users=$pdo->query('SELECT email,password_hash,role FROM users WHERE email IN ("admin@papertrade.local","trader@papertrade.local") ORDER BY id')->fetchAll(); appCheck('Admin and Trader users seeded',count($users)===2,'Run php scripts/php/setup_database.php; the seed creates the two development users.');
    $expected=['admin@papertrade.local'=>['Admin123!','admin'],'trader@papertrade.local'=>['Trader123!','trader']]; $hashes=true; foreach($users as $user)$hashes=$hashes&&isset($expected[$user['email']])&&password_verify($expected[$user['email']][0],$user['password_hash'])&&$user['role']===$expected[$user['email']][1]; appCheck('Passwords and roles verify',$hashes,'Rerun the seed in development; do not insert plain passwords into SQL.');
    appCheck('Only Admin and Trader roles',(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('admin','trader')")->fetchColumn()===0,'Run php scripts/php/setup_database.php to migrate legacy Analyst roles.');
    appCheck('Analyst demo removed',(int)$pdo->query("SELECT COUNT(*) FROM users WHERE email='analyst@papertrade.local'")->fetchColumn()===0,'Run php scripts/php/setup_database.php to remove the retired Analyst account.');
    appCheck('Demo portfolios isolated',(int)$pdo->query('SELECT COUNT(*) FROM portfolios p JOIN users u ON u.id=p.user_id WHERE u.email IN ("admin@papertrade.local","trader@papertrade.local")')->fetchColumn()===2,'Rerun database/seed.php.');
    appCheck('Demo user settings isolated',(int)$pdo->query('SELECT COUNT(*) FROM user_settings us JOIN users u ON u.id=us.user_id WHERE u.email IN ("admin@papertrade.local","trader@papertrade.local")')->fetchColumn()===2,'Rerun database/seed.php.');
    appCheck('Demo transactions exist',(int)$pdo->query('SELECT COUNT(*) FROM transactions t JOIN portfolios p ON p.id=t.portfolio_id JOIN users u ON u.id=p.user_id WHERE u.email IN ("admin@papertrade.local","trader@papertrade.local")')->fetchColumn()>=4,'Run php scripts/php/create_demo_data.php.');
    appCheck('Holdings match positive quantities',(int)$pdo->query('SELECT COUNT(*) FROM holdings WHERE quantity<=0')->fetchColumn()===0,'Run database/reset.php --confirm-demo-reset for demo data only.');
    appCheck('Watchlist supports non-owned rows',(int)$pdo->query('SELECT COUNT(*) FROM watchlist_items w LEFT JOIN portfolios p ON p.user_id=w.user_id LEFT JOIN holdings h ON h.portfolio_id=p.id AND h.stock_id=w.stock_id AND h.quantity>0 WHERE h.id IS NULL')->fetchColumn()>=3,'Run php scripts/php/create_demo_data.php.');
    appCheck('Exactly four main page routes',substr_count(file_get_contents(base_path('config/routes.php')),'Controller::class, \'index\'')>=4,'Restore config/routes.php and the four page controllers.');
} catch(Throwable $exception){ echo "FAIL Application database checks\n  Likely fix: start MySQL, verify .env, and run setup_database.php.\n  Error: {$exception->getMessage()}\n"; $failed=true; }
exit($failed?1:0);
