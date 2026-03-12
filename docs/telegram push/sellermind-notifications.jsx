import { useState } from "react";

const MARKETPLACES = {
  uzum: {
    name: "Uzum Market",
    emoji: "🟣",
    color: "#7B2FBE",
    colorLight: "#F3EAFF",
    gradient: "linear-gradient(135deg, #7B2FBE 0%, #A855F7 100%)",
  },
  wildberries: {
    name: "Wildberries",
    emoji: "🟪",
    color: "#CB11AB",
    colorLight: "#FDEAF7",
    gradient: "linear-gradient(135deg, #CB11AB 0%, #E040BF 100%)",
  },
  ozon: {
    name: "Ozon",
    emoji: "🔵",
    color: "#005BFF",
    colorLight: "#E6F0FF",
    gradient: "linear-gradient(135deg, #005BFF 0%, #3D8BFF 100%)",
  },
  yandex: {
    name: "Yandex Market",
    emoji: "🟡",
    color: "#FFCC00",
    colorLight: "#FFF9E0",
    gradient: "linear-gradient(135deg, #FFCC00 0%, #FFE066 100%)",
    textColor: "#8B7000",
  },
  online: {
    name: "Интернет-магазин",
    emoji: "🌐",
    color: "#10B981",
    colorLight: "#ECFDF5",
    gradient: "linear-gradient(135deg, #10B981 0%, #34D399 100%)",
  },
  offline: {
    name: "Оффлайн продажа",
    emoji: "🏪",
    color: "#F59E0B",
    colorLight: "#FFFBEB",
    gradient: "linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%)",
    textColor: "#92400E",
  },
};

const STATUS_MAP = {
  new: { label: "Новый", emoji: "🆕", color: "#3B82F6" },
  assembling: { label: "В сборке", emoji: "📦", color: "#F59E0B" },
  shipped: { label: "Отправлен", emoji: "🚚", color: "#8B5CF6" },
  delivered: { label: "Доставлен", emoji: "✅", color: "#10B981" },
  cancelled: { label: "Отменён", emoji: "❌", color: "#EF4444" },
  returned: { label: "Возврат", emoji: "↩️", color: "#EF4444" },
};

const SAMPLE_ORDERS = {
  uzum: {
    orderId: "97122751",
    account: "FORRIS HOME",
    status: "new",
    items: [
      { name: "Комплект постельного белья Сатин 2сп", qty: 1, price: 89900 },
      { name: "Подушка 50x70 хлопок", qty: 2, price: 16890 },
    ],
    total: 123680,
    delivery: "Самовывоз — ПВЗ Коканд, ул. Истиклол 45",
    warehouse: "Склад Коканд-1",
    buyer: "Азиза М.",
    dailyOrders: 14,
    dailyRevenue: 1847200,
    time: "11:26",
  },
  wildberries: {
    orderId: "WB-38291045",
    account: "FORRIS HOME",
    status: "assembling",
    items: [
      { name: "Одеяло евро 200x220 микрофибра", qty: 1, price: 245000 },
    ],
    total: 245000,
    delivery: "Курьер — Москва, Пресненская наб. 12",
    warehouse: "Склад WB Коледино",
    buyer: "Дмитрий К.",
    dailyOrders: 8,
    dailyRevenue: 967400,
    time: "14:02",
  },
  ozon: {
    orderId: "OZ-1029384756",
    account: "FORRIS HOME",
    status: "shipped",
    items: [
      { name: "Плед 150x200 флис", qty: 1, price: 67500 },
      { name: "Наволочки 50x70 (2шт)", qty: 1, price: 24900 },
      { name: "Простыня на резинке 160x200", qty: 1, price: 45600 },
    ],
    total: 138000,
    delivery: "ПВЗ Ozon — Ташкент, ул. Навои 22",
    warehouse: "Склад Ташкент-2",
    buyer: "Наргиза А.",
    dailyOrders: 5,
    dailyRevenue: 523700,
    time: "09:15",
  },
  yandex: {
    orderId: "YM-7654321",
    account: "FORRIS HOME",
    status: "new",
    items: [{ name: "Покрывало стёганое 220x240", qty: 1, price: 189000 }],
    total: 189000,
    delivery: "Курьер — Самарканд, ул. Регистан 8",
    warehouse: "Склад Самарканд",
    buyer: "Бахтиёр У.",
    dailyOrders: 3,
    dailyRevenue: 412000,
    time: "16:45",
  },
  online: {
    orderId: "SM-20240315-042",
    account: "forris.uz",
    status: "new",
    items: [
      { name: "Комплект КПБ Жаккард Евро", qty: 1, price: 320000 },
      { name: "Подушка ортопедическая", qty: 2, price: 85000 },
    ],
    total: 490000,
    delivery: "Доставка — Коканд, ул. Турон 15",
    warehouse: "Основной склад",
    buyer: "Малика Р.",
    dailyOrders: 2,
    dailyRevenue: 740000,
    time: "18:30",
  },
  offline: {
    orderId: "OFF-20240315-007",
    account: "Магазин FORRIS",
    status: "delivered",
    items: [
      { name: "Полотенце банное 70x140 (3шт)", qty: 1, price: 67500 },
    ],
    total: 67500,
    delivery: "Самовывоз",
    warehouse: "Магазин Коканд",
    buyer: "Покупатель",
    dailyOrders: 11,
    dailyRevenue: 1230000,
    time: "13:20",
  },
};

function formatMoney(amount) {
  return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

function TelegramMessage({ marketplace, order }) {
  const mp = MARKETPLACES[marketplace];
  const status = STATUS_MAP[order.status];

  return (
    <div
      style={{
        background: "#FFFFFF",
        borderRadius: 14,
        padding: "14px 16px",
        maxWidth: 380,
        fontFamily:
          '-apple-system, "SF Pro Text", "Segoe UI", Roboto, sans-serif',
        fontSize: 14.5,
        lineHeight: 1.55,
        color: "#1A1A1A",
        position: "relative",
        boxShadow: "0 1px 3px rgba(0,0,0,0.08)",
      }}
    >
      {/* Header bar */}
      <div
        style={{
          background: mp.gradient,
          borderRadius: 8,
          padding: "8px 12px",
          marginBottom: 12,
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
        }}
      >
        <span
          style={{
            color: "#fff",
            fontWeight: 700,
            fontSize: 14,
            letterSpacing: 0.2,
          }}
        >
          {mp.emoji} {mp.name}
        </span>
        <span
          style={{
            background: "rgba(255,255,255,0.25)",
            borderRadius: 20,
            padding: "2px 10px",
            color: "#fff",
            fontSize: 12,
            fontWeight: 600,
          }}
        >
          {status.emoji} {status.label}
        </span>
      </div>

      {/* Order info */}
      <div style={{ marginBottom: 10 }}>
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "baseline",
            marginBottom: 2,
          }}
        >
          <span style={{ color: "#6B7280", fontSize: 12 }}>Заказ</span>
          <span style={{ color: "#6B7280", fontSize: 11 }}>{order.time}</span>
        </div>
        <div style={{ fontWeight: 700, fontSize: 15, letterSpacing: 0.3 }}>
          #{order.orderId}
        </div>
        <div style={{ color: "#6B7280", fontSize: 13, marginTop: 1 }}>
          {order.account}
        </div>
      </div>

      {/* Divider */}
      <div
        style={{
          height: 1,
          background: "#F3F4F6",
          margin: "8px 0",
        }}
      />

      {/* Items */}
      <div style={{ marginBottom: 8 }}>
        <div
          style={{
            color: "#6B7280",
            fontSize: 11,
            textTransform: "uppercase",
            letterSpacing: 1,
            marginBottom: 6,
            fontWeight: 600,
          }}
        >
          Товары
        </div>
        {order.items.map((item, i) => (
          <div
            key={i}
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "flex-start",
              marginBottom: 4,
              gap: 8,
            }}
          >
            <span style={{ fontSize: 13, flex: 1 }}>
              {item.qty > 1 ? `${item.qty}× ` : ""}
              {item.name}
            </span>
            <span
              style={{
                fontSize: 13,
                fontWeight: 600,
                whiteSpace: "nowrap",
                color: "#374151",
              }}
            >
              {formatMoney(item.price)} сум
            </span>
          </div>
        ))}
      </div>

      {/* Total */}
      <div
        style={{
          background: mp.colorLight,
          borderRadius: 8,
          padding: "8px 12px",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          marginBottom: 10,
        }}
      >
        <span style={{ fontWeight: 600, fontSize: 13 }}>💰 Итого</span>
        <span
          style={{
            fontWeight: 800,
            fontSize: 17,
            color: mp.textColor || mp.color,
          }}
        >
          {formatMoney(order.total)} сум
        </span>
      </div>

      {/* Details grid */}
      <div
        style={{
          display: "grid",
          gridTemplateColumns: "1fr 1fr",
          gap: 6,
          marginBottom: 10,
        }}
      >
        <DetailCard icon="📍" label="Доставка" value={order.delivery} />
        <DetailCard icon="🏭" label="Склад" value={order.warehouse} />
        <DetailCard icon="👤" label="Покупатель" value={order.buyer} />
        <DetailCard
          icon="📊"
          label="За сегодня"
          value={`${order.dailyOrders} заказов · ${formatMoney(order.dailyRevenue)} сум`}
        />
      </div>

      {/* CTA Button */}
      <div
        style={{
          background: mp.gradient,
          borderRadius: 8,
          padding: "10px 16px",
          textAlign: "center",
          cursor: "pointer",
          transition: "opacity 0.2s",
        }}
      >
        <span
          style={{
            color: "#fff",
            fontWeight: 700,
            fontSize: 13,
            letterSpacing: 0.3,
          }}
        >
          📋 Открыть в SellerMind →
        </span>
      </div>
    </div>
  );
}

function DetailCard({ icon, label, value }) {
  return (
    <div
      style={{
        background: "#F9FAFB",
        borderRadius: 8,
        padding: "6px 10px",
      }}
    >
      <div
        style={{ fontSize: 11, color: "#9CA3AF", marginBottom: 2 }}
      >
        {icon} {label}
      </div>
      <div
        style={{
          fontSize: 12,
          color: "#374151",
          fontWeight: 500,
          lineHeight: 1.4,
        }}
      >
        {value}
      </div>
    </div>
  );
}

function TelegramCodePreview({ marketplace, order }) {
  const mp = MARKETPLACES[marketplace];
  const status = STATUS_MAP[order.status];
  const items = order.items
    .map(
      (i) =>
        `   ${i.qty > 1 ? i.qty + "× " : "• "}${i.name} — ${formatMoney(i.price)} сум`
    )
    .join("\n");

  const text = `${mp.emoji} <b>${mp.name}</b>  │  ${status.emoji} ${status.label}
━━━━━━━━━━━━━━━━━━━━

📋 <b>Заказ #${order.orderId}</b>
🏬 ${order.account}

🛒 <b>Товары:</b>
${items}

💰 <b>Итого: ${formatMoney(order.total)} сум</b>

📍 ${order.delivery}
🏭 ${order.warehouse}
👤 ${order.buyer}

━━━━━━━━━━━━━━━━━━━━
📊 Сегодня: <b>${order.dailyOrders}</b> заказов · <b>${formatMoney(order.dailyRevenue)}</b> сум`;

  return (
    <pre
      style={{
        background: "#1E1E2E",
        color: "#CDD6F4",
        borderRadius: 12,
        padding: 16,
        fontSize: 12.5,
        lineHeight: 1.6,
        fontFamily: '"JetBrains Mono", "Fira Code", monospace',
        overflowX: "auto",
        whiteSpace: "pre-wrap",
        wordBreak: "break-word",
      }}
    >
      {text}
    </pre>
  );
}

export default function App() {
  const [selected, setSelected] = useState("uzum");
  const [view, setView] = useState("preview");

  return (
    <div
      style={{
        minHeight: "100vh",
        background: "#0F0F14",
        color: "#E5E5E5",
        fontFamily:
          '"DM Sans", -apple-system, "Segoe UI", sans-serif',
      }}
    >
      <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet"
      />

      {/* Header */}
      <div
        style={{
          padding: "32px 24px 20px",
          textAlign: "center",
        }}
      >
        <div
          style={{
            fontSize: 11,
            textTransform: "uppercase",
            letterSpacing: 3,
            color: "#6B7280",
            marginBottom: 8,
            fontWeight: 600,
          }}
        >
          SellerMind
        </div>
        <h1
          style={{
            fontSize: 26,
            fontWeight: 800,
            margin: 0,
            background:
              "linear-gradient(135deg, #60A5FA 0%, #A78BFA 50%, #F472B6 100%)",
            WebkitBackgroundClip: "text",
            WebkitTextFillColor: "transparent",
            letterSpacing: -0.5,
          }}
        >
          Telegram-уведомления
        </h1>
        <p
          style={{
            color: "#6B7280",
            fontSize: 14,
            margin: "8px 0 0",
          }}
        >
          Дизайн уведомлений о заказах
        </p>
      </div>

      {/* Marketplace tabs */}
      <div
        style={{
          display: "flex",
          gap: 6,
          padding: "0 16px",
          overflowX: "auto",
          scrollbarWidth: "none",
          marginBottom: 16,
          flexWrap: "wrap",
          justifyContent: "center",
        }}
      >
        {Object.entries(MARKETPLACES).map(([key, mp]) => (
          <button
            key={key}
            onClick={() => setSelected(key)}
            style={{
              background:
                selected === key
                  ? mp.gradient
                  : "rgba(255,255,255,0.06)",
              border:
                selected === key
                  ? "none"
                  : "1px solid rgba(255,255,255,0.08)",
              borderRadius: 20,
              padding: "7px 14px",
              color: selected === key ? "#fff" : "#9CA3AF",
              fontSize: 12.5,
              fontWeight: selected === key ? 700 : 500,
              cursor: "pointer",
              transition: "all 0.2s",
              whiteSpace: "nowrap",
              fontFamily: "inherit",
            }}
          >
            {mp.emoji} {mp.name}
          </button>
        ))}
      </div>

      {/* View toggle */}
      <div
        style={{
          display: "flex",
          justifyContent: "center",
          gap: 4,
          marginBottom: 20,
        }}
      >
        {[
          { key: "preview", label: "👁 Превью" },
          { key: "code", label: "⌨ HTML-код" },
          { key: "php", label: "🐘 PHP" },
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setView(tab.key)}
            style={{
              background:
                view === tab.key
                  ? "rgba(255,255,255,0.12)"
                  : "transparent",
              border: "1px solid rgba(255,255,255,0.08)",
              borderRadius: 8,
              padding: "6px 16px",
              color: view === tab.key ? "#fff" : "#6B7280",
              fontSize: 13,
              fontWeight: 600,
              cursor: "pointer",
              fontFamily: "inherit",
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Content */}
      <div
        style={{
          display: "flex",
          justifyContent: "center",
          padding: "0 16px 40px",
        }}
      >
        {view === "preview" && (
          <div>
            {/* Telegram-like background */}
            <div
              style={{
                background:
                  "linear-gradient(180deg, #1B3A4B 0%, #1A2F3A 50%, #162029 100%)",
                borderRadius: 20,
                padding: "24px 20px",
                maxWidth: 420,
                boxShadow: "0 20px 60px rgba(0,0,0,0.4)",
              }}
            >
              {/* Bot header */}
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 10,
                  marginBottom: 16,
                  paddingBottom: 12,
                  borderBottom: "1px solid rgba(255,255,255,0.08)",
                }}
              >
                <div
                  style={{
                    width: 36,
                    height: 36,
                    borderRadius: "50%",
                    background:
                      "linear-gradient(135deg, #3B82F6, #8B5CF6)",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    fontSize: 18,
                  }}
                >
                  🧠
                </div>
                <div>
                  <div
                    style={{
                      color: "#fff",
                      fontWeight: 700,
                      fontSize: 14,
                    }}
                  >
                    SellerMind Bot
                  </div>
                  <div
                    style={{
                      color: "#4ADE80",
                      fontSize: 11,
                    }}
                  >
                    онлайн
                  </div>
                </div>
              </div>

              <TelegramMessage
                marketplace={selected}
                order={SAMPLE_ORDERS[selected]}
              />
            </div>

            <div
              style={{
                textAlign: "center",
                marginTop: 16,
                color: "#4B5563",
                fontSize: 12,
              }}
            >
              Выберите маркетплейс для просмотра
            </div>
          </div>
        )}

        {view === "code" && (
          <div style={{ maxWidth: 520, width: "100%" }}>
            <div
              style={{
                color: "#9CA3AF",
                fontSize: 12,
                marginBottom: 8,
                fontWeight: 600,
              }}
            >
              HTML-шаблон для Telegram Bot API (parse_mode: HTML)
            </div>
            <TelegramCodePreview
              marketplace={selected}
              order={SAMPLE_ORDERS[selected]}
            />
          </div>
        )}

        {view === "php" && (
          <div style={{ maxWidth: 600, width: "100%" }}>
            <div
              style={{
                color: "#9CA3AF",
                fontSize: 12,
                marginBottom: 8,
                fontWeight: 600,
              }}
            >
              PHP-метод для Laravel — формирование и отправка
            </div>
            <pre
              style={{
                background: "#1E1E2E",
                color: "#CDD6F4",
                borderRadius: 12,
                padding: 16,
                fontSize: 12,
                lineHeight: 1.6,
                fontFamily:
                  '"JetBrains Mono", "Fira Code", monospace',
                overflowX: "auto",
                whiteSpace: "pre-wrap",
                wordBreak: "break-word",
              }}
            >
              {`<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\Http;

class TelegramNotificationService
{
    private const MARKETPLACE_CONFIG = [
        'uzum'    => ['emoji' => '🟣', 'name' => 'Uzum Market'],
        'wb'      => ['emoji' => '🟪', 'name' => 'Wildberries'],
        'ozon'    => ['emoji' => '🔵', 'name' => 'Ozon'],
        'yandex'  => ['emoji' => '🟡', 'name' => 'Yandex Market'],
        'online'  => ['emoji' => '🌐', 'name' => 'Интернет-магазин'],
        'offline' => ['emoji' => '🏪', 'name' => 'Оффлайн'],
    ];

    private const STATUS_MAP = [
        'new'        => ['emoji' => '🆕', 'label' => 'Новый'],
        'assembling' => ['emoji' => '📦', 'label' => 'В сборке'],
        'shipped'    => ['emoji' => '🚚', 'label' => 'Отправлен'],
        'delivered'  => ['emoji' => '✅', 'label' => 'Доставлен'],
        'cancelled'  => ['emoji' => '❌', 'label' => 'Отменён'],
        'returned'   => ['emoji' => '↩️', 'label' => 'Возврат'],
    ];

    public function sendOrderNotification(
        Order $order,
        string $chatId
    ): void {
        $mp = self::MARKETPLACE_CONFIG[$order->marketplace];
        $status = self::STATUS_MAP[$order->status];

        // Формируем список товаров
        $items = $order->items->map(function ($item) {
            $prefix = $item->quantity > 1
                ? $item->quantity . '× '
                : '• ';
            return "   {$prefix}{$item->name} — "
                . number_format($item->price, 0, '.', ' ')
                . " сум";
        })->implode("\\n");

        // Статистика за день
        $daily = Order::where('marketplace', $order->marketplace)
            ->where('account_id', $order->account_id)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as cnt, SUM(total) as rev')
            ->first();

        $text = <<<HTML
{$mp['emoji']} <b>{$mp['name']}</b>  │  {$status['emoji']} {$status['label']}
━━━━━━━━━━━━━━━━━━━━

📋 <b>Заказ #{$order->external_id}</b>
🏬 {$order->account->name}

🛒 <b>Товары:</b>
{$items}

💰 <b>Итого: {$this->formatMoney($order->total)} сум</b>

📍 {$order->delivery_address}
🏭 {$order->warehouse->name}
👤 {$order->buyer_name}

━━━━━━━━━━━━━━━━━━━━
📊 Сегодня: <b>{$daily->cnt}</b> заказов · <b>{$this->formatMoney($daily->rev)}</b> сум
HTML;

        // Inline-кнопка для перехода в SellerMind
        $keyboard = [
            'inline_keyboard' => [[
                [
                    'text' => '📋 Открыть в SellerMind',
                    'url'  => config('app.url')
                        . "/orders/{$order->id}",
                ],
            ]],
        ];

        Http::post(
            "https://api.telegram.org/bot"
            . config('services.telegram.bot_token')
            . "/sendMessage",
            [
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode'   => 'HTML',
                'reply_markup' => json_encode($keyboard),
            ]
        );
    }

    private function formatMoney(int|float $amount): string
    {
        return number_format($amount, 0, '.', ' ');
    }
}`}
            </pre>
          </div>
        )}
      </div>
    </div>
  );
}