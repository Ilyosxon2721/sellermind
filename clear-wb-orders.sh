#!/bin/bash
# Очистка заказов Wildberries для пересинхронизации

cd "$(dirname "$0")"

echo "⚠️  ВНИМАНИЕ: Этот скрипт удалит все заказы Wildberries из базы данных!"
echo ""
read -p "Вы уверены? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Отменено."
    exit 1
fi

echo ""
echo "🗑️  Удаление заказов Wildberries..."

# Получаем ID аккаунта Wildberries
ACCOUNT_ID=$(php artisan tinker --execute="
    \$account = App\Models\MarketplaceAccount::where('marketplace', 'wildberries')->first();
    echo \$account ? \$account->id : 'not_found';
")

if [ "$ACCOUNT_ID" = "not_found" ]; then
    echo "❌ Аккаунт Wildberries не найден!"
    exit 1
fi

echo "📋 ID аккаунта Wildberries: $ACCOUNT_ID"

# Удаляем все заказы этого аккаунта
php artisan tinker --execute="
    \$deleted = DB::table('marketplace_orders')
        ->where('marketplace_account_id', $ACCOUNT_ID)
        ->delete();
    echo 'Удалено заказов: ' . \$deleted;
"

echo ""
echo "✅ Заказы удалены!"
echo ""
echo "📊 Теперь запустите синхронизацию в браузере, чтобы получить актуальные данные."
