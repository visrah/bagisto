<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'תשלומי כרטיס באמצעות Absa Spark',
        'description' => 'קבלו תשלומי כרטיס ב-MUR דרך דף התשלום המאורח של Absa Spark. הלקוחות משלמים בדף המאובטח של Absa, ולכן פרטי הכרטיס אינם מגיעים לחנות שלכם.',
        'enabled' => 'הפעלת Absa Spark',
        'yes' => 'כן',
        'no' => 'לא',
        'checkout-title' => 'השם המוצג בקופה',
        'mode' => 'מצב',
        'sandbox' => 'ארגז חול (בדיקות)',
        'live' => 'חי',
        'store-id' => 'מזהה חנות',
        'api-key' => 'מפתח API',
        'secret' => 'סוד API',
        'saved-secret' => 'קיים ערך שמור. השאירו ריק כדי לשמור עליו.',
        'not-set' => 'עדיין לא הוגדר.',
        'save' => 'שמירה',
        'updated' => 'הגדרות Absa Spark נשמרו.',
        'mur-only' => 'Absa Spark מקבל MUR בלבד. מטבע החנות שלכם הוא :currency, ולכן אמצעי התשלום הזה נשאר מוסתר בקופה.',
        'credentials-required' => 'הזינו מזהה חנות, מפתח API וסוד לפני הפעלת Absa Spark.',
        'webhook-url' => 'כתובת Webhook',
        'webhook-hint' => 'מסרו כתובת זו ל-Absa כדי שתוכל לאשר תשלומים. היא חייבת להיות נגישה ב-HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'לא הצלחנו להגיע לדף התשלום. לא חויבתם. נסו שוב.',
        'cart-missing' => 'העגלה שלכם לא נמצאה. בדקו את העגלה ונסו שוב.',
        'cart-invalid' => 'לא ניתן לשלם על העגלה שלכם כעת. בדקו אותה ונסו שוב.',
        'mur-only' => 'תשלום בכרטיס דרך Absa זמין ב-MUR בלבד.',
        'confirming' => 'אנו מאשרים את התשלום שלכם. תקבלו אימייל לאחר השלמתו.',
        'declined' => 'התשלום נדחה. לא חויבתם. נסו שוב.',
        'cancelled' => 'התשלום בוטל. לא חויבתם.',
        'late-payment' => 'Absa גבתה תשלום (אסמכתא :reference) לאחר ביטול ההזמנה הזו. החזירו ללקוח את הכסף.',
    ],
];
