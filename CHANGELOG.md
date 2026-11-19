# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.35.2] - 2026-11-19

### Fixed

- **ستونِ صفِ انتظارِ تقویم در روزِ آخرِ ماهِ شمسی صفر می‌شد**: کوئریِ صفِ ماه با
  کرانِ بالای «Y-m-d» خالیِ رشته‌ای بسته می‌شد، درحالی‌که SQLite مقدارِ ستونِ
  تاریخ را «Y-m-d H:i:s» ذخیره می‌کند — و «2026-11-21 00:00:00» در مقایسهٔ
  رشته‌ای از «2026-11-21» بزرگ‌تر است. نتیجه: درست از آبانِ ۳۰ به بعد، تقاضایِ
  صفِ آن روز از خانه‌های تقویم افتاده بود — همان که CI امروز را قرمز کرد و
  بازتولیدش در ۳ از ۳ ران شد. کران‌ها حالا مثل بقیهٔ کوئری‌های رنجِ همین فایل
  با startOfDay/endOfDay بسته می‌شوند که هر دو فرمتِ ذخیره‌سازی (تاریخِ خالیِ
  MySQL و دیت‌تایمِ SQLite) را دربر می‌گیرد؛ و تستِ «شمارشِ تقاضای صف» — که با
  nextOpenDate چنین روزی می‌سازد — از این‌پس این مسیر را همیشه پوشش می‌دهد.

## [1.35.1] - 2026-11-18

### Security

- **minimatch@9.0.9 آسیب‌پذیر (DoS از راه amplification)**: overrideِ جراحی در
  package.json مسیرِ `vue-tsc → @vue/language-core` را به `^11.0.9` برد — همان
  نسخه‌ای که زیرِ typescript-eslint از قبل نشسته بود؛ minimatch@3.1.5 زیرِ eslint
  بیرونِ بازهٔ هشدار است و دست‌نخورده ماند. هشدارِ Dependabot با این پوش بسته می‌شود.
- **picomatch (HIGH — ReDoS در extglobهای تک‌شاخه)**: نسخهٔ ترمیم‌شده‌ای روی
  رجیستری نیست (`<= 4.0.7` همهٔ نسخه‌های موجود را دربر می‌گیرد) و «راه‌حلِ»
  پیشنهادیِ npm یعنی downgradeِ vite/tailwind نه واقعی است نه امن. آسیب‌پذیری فقط
  در ابزارِ dev است (watcher ویت، اسکنِ محتوای tailwind، eslint) و در دارایی‌های
  ساخته‌شده هیچ ردِ پایی ندارد؛ به‌محضِ انتشارِ نسخهٔ پچ‌شده، override اضافه می‌شود.

### Fixed

- نسخهٔ اپ (`config/app.php`) که از 1.34.0 جا مانده بود — بجِ لندینگ و `/health`
  هنوز «1.34.0» می‌گفتند؛ حالا با هر ریلیز واقعاً بامپ می‌شود.

## [1.35.0] - 2026-11-18

### Added

- **کدِ رزروِ پیشخوان**: در جدولِ کسب‌وکارها (سوپرادمین) و کنارِ لینکِ عمومی در
  صفحهٔ گالری (مدیر)، دکمهٔ «کد QR» دیالوگی باز می‌کند که QRِ صفحهٔ عمومیِ رزروِ
  همان کسب‌وکار را می‌کشد — با دانلودِ PNG باکیفیت (۵۱۲px) و کپیِ لینک؛ چاپش روی
  پیشخوان یعنی مشتری با یک اسکن در صفحهٔ رزرو است. کاملاً سمتِ مرورگر با همان
  `qrcode` که رسیدِ نوبت می‌کشد، سه‌زبانه (fa/en/ar) و بدونِ هیچ تغییرِ بک‌اند.
- **ریلیزِ خودکارِ گیت‌هاب**: پوشِ هر تگِ `v*` یک GitHub Release می‌سازد که متنش
  دقیقاً بخشِ همان نسخه از همین CHANGELOG است (`.github/extract_changelog.py` —
  اگر نسخه چنجلاگ نداشته باشد ریلیز با خطا می‌شکند تا خالی منتشر نشود)؛ بجِ
  «release» در هر دو README مستقیم از تگ‌های گیت‌هاب می‌خواند.

### Fixed

- تستِ «ردِ جابه‌جاییِ نوبتی که زمانش شروع شده» قطعی شد: با `CarbonImmutable::setTestNow`
  ساعتِ روی ۱۰ صبح قفل شد تا نوبتِ «امروز ۰۸:۰۰» همیشه گذشته باشد — رانِ CI که پیش
  از ۰۸:۰۰ UTC می‌افتاد تست را flaky کرده بود (قرمزِ ۲۵ آبان از همین بود).
- دو ایرادِ واقعیِ ESLint که تا امروز در CI دیده نمی‌شد: در صفحهٔ مشتریانِ داشبورد
  تابعِ `search` با prop همنامِ `search` تصادم داشت (`vue/no-dupe-keys`) و تابع به
  `submitSearch` تغییرِ نام داد؛ در نوبت‌هایِ من یک متغیرِ بلااستفاده حذف شد.

### Changed

- **چیدمانِ استانداردِ وابستگی‌های npm**: ابزارهای فقط-ساخت (vite، tailwindcss،
  typescript، vue-tsc، autoprefixer، laravel-vite-plugin و @types/*) به
  `devDependencies` منتقل شدند و `lucide` (نسخهٔ بلااستفادهٔ غیر-Vue) حذف شد —
  `npm audit --omit=dev` از ۱۶ آسیب‌پذیری به صفر رسید؛ vite نیز به ۶.۴.۶ ارتقا
  یافت (خطای امنیتی تا ۶.۴.۳ را می‌پوشاند). ارتقای major باقی‌ماندهٔ tailwind ۴
  و advisory های بالادستیِ بدونِ فیکس (sucrase، tinyglobby) به کارِ جداگانه موکول شد.
- ۲۸ فایلِ فرانت‌اند با Prettier قالب‌بندی شد و `format:check`/`lint:check` به
  CI اضافه شد — از این پس ESLint و Prettier همراهِ Pint در هر push اجرا می‌شوند.
- اسکرین‌شات‌های واقعیِ دموی زنده: تقویمِ بصریِ v1.34 و داشبوردِ تازه‌شده به
  گالریِ هر دو README اضافه شد و رودمپِ انگلیسی که از v1.16 عقب‌مانده بود تا
  v1.35 همگام شد.

## [1.34.0] - 2026-11-17

### Added

- **تقویمِ بصریِ پنل — ماهِ کاری در یک نگاه**: صفحهٔ «تقویم» در داشبورد یک ماه
  جلالیِ کامل را می‌کشد؛ هر روز خانه‌ای است با بارِ واقعی‌اش — شمارشِ وضعیت‌ها
  (تأییدشده، در انتظار، انجام‌شده، لغوشده)، دقیقه‌های اشغال‌شده و نشانِ صفِ
  انتظار — و شدتِ سبزیِ هر خانه با بارِ همان روز بالا می‌رود؛ جمعه‌ها رنگِ
  تعطیلی می‌گیرند، «امروز» با حاشیهٔ مشخص است و گذشته‌ها کم‌رنگ‌اند. کلیک روی
  هر روز خطِ زمانِ همان روز را می‌آورد: نوبت‌ها با ساعت و مشتری و خدمت و استِف
  و نشانِ وضعیت، به‌همراه صفِ انتظارِ همان روز. کاهندگی و جهتِ ماه با همان
  موتورِ `jalaali-js` می‌چرخد که سمت PHP پورت شد (`App\Support\Jalali`) و روی
  ۳۴۳ تاریخ با نسخهٔ TypeScript مرورگر هم‌مقیاس شد؛ مدیرها تقویمِ کسب‌وکارِ
  خودشان را می‌بینند، سوپرادمین با انتخابِ کسب‌وکار کل را می‌بیند و استِف فقط
  نوبت‌های خودش را — همه از مسیرِ همان `visibleTo` آشنا.

### Changed

- تست‌ها: ۳۱۶ → **۳۳۰**؛ نسخهٔ اپ به 1.34.0 رسید.

## [1.33.0] - 2026-11-16

### Added

- **پایشِ نسل دوم — ورکرِ گِرِفته پیش از گِرِفتنِ کاربر دیده می‌شود**: `/health`
  حالا به‌ازای صفِ دیتابیسی **عمقِ صف**، **سنِ کهن‌ترین job منتظر** (عددی که با
  گِرِفتنِ ورکر رشد می‌کند — همان OTP که هیچ‌وقت نرسید)، **شمارشِ failedها**،
  **فضای آزادِ دیسک** و **نسخهٔ اپ** جواب می‌دهد؛ و دستورِ `nobat:doctor` همهٔ
  همین چک‌ها را یک‌جا در یک جدول می‌دود — دیتابیس، دیسک، cache، ضربانِ زمان‌بند،
  صف، درایورهای پیامک/پرداخت و APP_KEY — و با کدِ خروجِ ناصفر به کرون و
  اسکریپتِ دیپلوی اجازه می‌دهد رویش گیت بگذارند. نقشهٔ راهِ README هم با
  یازده ریلیزی که از v1.16 به بعد روایتشان را قطع کرده بود، به‌روز شد.

### Changed

- تست‌ها: ۳۱۲ → **۳۱۶**؛ نسخهٔ اپ به 1.33.0 رسید.

## [1.32.0] - 2026-11-16

### Added

- **پیامکِ بین‌المللی — مهاجرت‌ها هم پیام می‌گیرند**: تعهدِ عقب‌افتادهٔ مرحلهٔ ۱
  فرود آمد؛ درایورِ `Twilio` با نرمال‌سازی E.164 (ورودیِ `0912…` خودش `+98`
  می‌شود) و احراز basic و شکستِ بلندِ هم‌طرازِ کاوه‌نگار، و درایورِ `intl`
  مسیرِ هر پیام را با مقصدش انتخاب می‌کند — ایرانی‌ها (`09…`, `+98…`, `989…`)
  با کاوه‌نگار می‌روند و بقیهٔ دنیا با Twilio — تا کسب‌وکاری با مشتریِ
  خارج‌نشسته فقط یک درایور انتخاب کند و دیگر باتوجه به مقصد، نگرانِ
  شلیکِ درگاه نباشد. دفتر عملیات §۸ کلِ ریل پیامک را می‌نویسد.

### Changed

- تست‌ها: ۳۰۵ → **۳۱۲**؛ نسخهٔ اپ به 1.32.0 رسید.

## [1.31.0] - 2026-11-16

### Added

- **صفِ واقعی روی دمو — پایپ‌لاینِ دفترِ عملیات، همین حالا کار می‌کند**: دفترِ
  عملیات از روزِ اول می‌گفت «jobها در جدولِ jobs می‌نشینند و ورکر با تلاشِ
  مجدد برمی‌دارد»؛ دمو اما با `sync` همه‌چیز را درونِ request اجرا می‌کرد. حالا
  دمو با `QUEUE_CONNECTION=database` می‌چرخد و اسکریپتِ بوت، ورکرِ `queue:work`
  را کنارِ شِدولر بالا می‌آورد — هر پیامک (OTP، رزرو، یادآوری، پرداخت) واقعاً
  صف می‌شود و با `tries=3` پردازش می‌شود. توسعه‌ی محلی با `sync` می‌ماند و
  ورکر نمی‌خواهد.

### Changed

- تست‌ها: ۳۰۳ → **۳۰۵**؛ نسخهٔ اپ به 1.31.0 رسید.

## [1.30.1] - 2026-11-16

### Fixed

- **callbackِ verifyِ پرداخت روی دمو دیگر ۵۰۰ نمی‌شود**: شِدولرِ هر-دقیقه‌ای و
  درخواستِ کاربر هم‌زمان روی SQLite می‌نویسند و `busy_timeout` صفر یعنی انفجارِ
  فوری؛ حالا اتصالِ sqlite با `busy_timeout=۵۰۰۰ms` و WAL می‌نشیند — verifyها و
  استردادها روی قفل صبر می‌کنند، نه منفجر شدن. راستی‌آزماییِ زنده این باگ را بیرون
  کشید: تأییدِ زرین‌پال درست رد شد («Session is not valid») اما updateِ ردیفِ
  پرداخت با قفلِ دیتابیس از پا درآمد — دقیقاً همان جفت‌نوشتنِ ریز-ساعتِ sweep.
  تست‌ها: ۳۰۳ (بدون تغییر)؛ نسخهٔ اپ به 1.30.1 رسید.

## [1.30.0] - 2026-11-16

### Added

- **فاکتور PDF — رسیدِ واقعی، نه یک برگهٔ چاپی**: برگهٔ چاپی به فونت‌های خودِ بازدیدکننده
  تکیه داشت؛ حالا همان snapshot منجمد با mPDF و فونتِ همراهِ خودش (XB Riyaz) به یک
  PDF فارسیِ درست‌شکل‌دهی‌شده می‌رسد — RTL واقعی، جدول‌ها، مبلغ، کد تراکنش و درگاه.
  دانلود پشت همان امضای صفحهٔ چاپ می‌ماند (فاکتور PII دارد) و لینکش کنار لینک فاکتور
  در صفحهٔ پیگیری و روی ردیف‌های پرداخت‌شدهٔ «نوبت‌های من» نشسته. تست‌ها بایت‌های واقعی
  را می‌سنجند (جادوی PDF، کاتالوگ، حجم) و لینکِ امضادار را در payload صفحهٔ پیگیری.

### Changed

- تست‌ها: ۳۰۰ → **۳۰۳**؛ نسخهٔ اپ به 1.30.0 رسید.

## [1.29.0] - 2026-11-16

### Added

- **لغوِ نوبتِ پرداخت‌شده — پول قبل از اسلات حرکت می‌کند**: تا امروز نوبتِ پرداخت‌شده
  برای مشتری غیرقابل‌لغو بود («از کسب‌وکار بخواهید»)؛ حالا مسیر مهمان و مسیر صاحبِ
  حساب هر دو لغوِ پرداخت‌شده را با استردادِ خودکار انجام می‌دهند — بازپرداخت باید
  برسد تا اسلات آزاد شود؛ اگر درگاه ناکام بماند نوبت و پول هر دو سرِ جایشان می‌مانند
  و مشتری به retry دعوت می‌شود، غریبه هنوز اثباتِ شماره می‌خواهد، و تلاشِ دومِ بعد
  از استردادِ موفق را گیتِ وضعیت می‌بندد تا هیچ پولی دوبار حرکت نکند. کارتِ «نوبت‌های
  من» همین گیت را آینه می‌کند و درگاهِ زرین‌پال تست‌های بازپرداختش را می‌گیرد:
  استردادِ سندباکس با `simulated`، و رفضِ بلندِ بازپرداختِ پروداکت که مالِ پنلِ
  فروشنده است.

### Changed

- تست‌ها: ۲۹۳ → **۳۰۰**؛ نسخهٔ اپ به 1.29.0 رسید.

## [1.28.0] - 2026-11-16

### Added

- **درگاهِ واقعی روی دمو — پول واقعی، ریل واقعی**: تا امروز پرداختِ دمو با درگاهِ
  ساختگی `log` شبیه‌سازی می‌شد؛ حالا دمو با درایور `zarinpal` روی سندباکسِ واقعیِ
  زرین‌پال می‌رود — دکمهٔ پرداخت ویزارد، بازدیدکننده را به `StartPay` واقعی می‌برد و
  بازگشتِ واقعی به callback با تأییدِ سه‌لایهٔ idempotent (ایندکس یکتای authority،
  قفل cache، گارد تسویه) تمام می‌شود. سندباکس شناسهٔ فروشندهٔ همه‌صفر را می‌پذیرد و
  دفتر عملیات (§۷) کلِ ریل را می‌نویسد: انتخاب درایور، مبلغِ تومان→ریال، کد ۱۰۱،
  استردادِ خودکار و رفتارِ پولِ بعد از انقضای قفل اسلات.

### Changed

- نسخهٔ اپ به 1.28.0 رسید — مایلستونِ راه‌اندازی؛ رفتارِ کد تغییری نکرده.

## [1.27.0] - 2026-11-16

### Added

- **دفتر عملیات، قدم به قدم با حساب‌های موبایلی**: بخش زمان‌بند حالا سه کار را
  فهرست می‌کند — `nobat:prune-login-otps` روزانه به جدول پیوست — و بخش صف صراحت
  می‌گوید پیامک‌های OTP هم روی همان صف می‌روند: با `sync` در دمو و با `database`
  در پروداکت، سه تلاش با backoff، و سقفِ نرخِ مستقلِ درخواستِ کد به‌عنوان دیوارِ
  ضدِ حملهٔ هزینه‌سازِ پیامک. بج تست‌ها در README هم به ۲۹۳ رسید.

### Changed

- نسخهٔ اپ به 1.27.0 رسید — ریلِ عملیاتی؛ کدِ محصول در این نسخه تغییری نکرده.

## [1.26.0] - 2026-11-16

### Added

- **پیامک با اسمِ حساب — نه اسمِ دفتر**: نوبت‌دارِ وارد‌شده، «مینا حسابی» است نه «مینا»ی
  مهمانِ قدیم. همهٔ صداهای پیامکی — رزرو، یادآوری، جابه‌جایی، دعوت‌نامهٔ نظر — حالا
  از `greetingName()` می‌خوانند: اگر نوبت صاحب دارد، امضای حساب؛ اگر مهمان است، همان
  اسم دفتر. یک آدم، یک املای اسم، هر جا پیامک حرف می‌زند.
- **پاک‌سازِ دفترِ OTP**: کدها دو بار می‌میرند — مصرف‌شده یا از پنج‌دقیقه‌ای گذشته —
  و یک روز بعد فقط نویزند. `nobat:prune-login-otps` آن‌ها را می‌بوید و شِدولِ روزانه
  می‌اندازدشان؛ کدهای جوانِ مرده هنوز یک شب مهلت دارند تا پنجرهٔ retry تمام شود.

### Changed

- تست‌ها: ۲۸۹ → **۲۹۳**؛ نسخهٔ اپ به 1.26.0 رسید.

## [1.25.0] - 2026-11-16

### Added

- **نوبت‌های من — دفترِ شخصیِ مشتری**: ورودِ بی‌رمز بدون یک صفحهٔ مال خودش بی‌معنا بود؛
  حالا حساب که ساخته شد، تاریخچه‌اش هم هست — نوبت‌های پیش‌رو و گذشته، نام کسب‌وکار و
  خدمت و پرسنل، هفته‌های بعدیِ سریِ دوره‌ای زیر رخدادِ اول، و اکشن‌ها سرِ جایشان:
  پرداختِ پیش‌نویس‌ها، پیگیری، افزودن به تقویم، و لغو **بدون اثباتِ دوبارهٔ مالکیت** —
  خودِ حساب اثباتِ مالکیت است؛ غریبهٔ واردشده هنوز شمارهٔ مهمان می‌خواهد. لغو و
  جابه‌جایی حالا از یک گیتِ دو-مسیره می‌گذرند (حسابِ صاحبِ نوبت یا شمارهٔ رزروکننده)
  و نرمال‌سازِ تکراریِ رقم‌ها در `App\Support\Phone` جمع شد. ورودِ OTP مستقیم به همین
  صفحه می‌رسد، لینک «نوبت‌های من» در ناوبریٔ عمومی کنار داشبورد نشسته، و نوبت‌های
  مهمانِ اتخاذشده د رکنار رزروهای بومیِ حساب می‌نشینند — یک دفتر، یک آدم.

### Changed

- تست‌ها: ۲۸۱ → **۲۸۹**؛ نسخهٔ اپ به 1.25.0 رسید.

## [1.24.0] - 2026-11-16

### Added

- **ورودِ بی‌رمز — شمارهٔ موبایل می‌شود کلید حساب**: تا امروز مشتری فقط مهمان بود —
  کد پیگیری، پیامک و QR. حالا شمارهٔ موبایلی که سال‌ها رزرو کرده، حساب است: صفحهٔ
  ورود دو مرحله‌ای، کد شش‌رقمی پیامک‌شده، بدون رمز، بدون فرم ثبت‌نام. کد هش‌شده ذخیره
  می‌شود (دیتابیسِ درزکرده هرگز کدِ کارا بیرون نمی‌دهد)، پنج دقیقه عمر دارد، پنج تلاشِ
  غلط می‌کُشدش — حتی اگر ششمین تلاش کد درست باشد — و درخواستِ کد سقفِ سه‌تا-در-پنج‌دقیقه
  به‌ازای هر شماره دارد، چون صندوقِ پرِ پیامک ارزان‌ترین حملهٔ اختلال‌ساز است. نخستین
  کدِ درست مشتری را می‌سازد (نامش را از دفترِ رزرو می‌آورد اگر این شماره قبلاً رزرو
  کرده، نقش customer می‌گیرد) و همهٔ نوبت‌های مهمانِ همان شماره را زیر حساب می‌آورد —
  شماره‌های ذخیره‌شده ممکن است رقمِ فارسی یا عربی، پیشوند +98 یا فاصلهٔ میانی داشته
  باشند؛ شبکهٔ پیش‌فیلتر هر سه خط را می‌شناسد و قضاوت نهایی را مقایسهٔ نرمال‌شدهٔ
  دقیق انجام می‌دهد. تست‌ها هر دو سوی هر قفل را می‌سنجند.

### Changed

- `users` حالا ستون `phone` یکتا دارد؛ `email` و `password` اختیاری شدند — مشتریِ
  تلفن‌محور هیچ‌کدام را ندارد و نباید داشته باشد.
- تست‌ها: ۲۷۲ → **۲۸۱**؛ نسخهٔ اپ به 1.24.0 رسید.

## [1.23.0] - 2026-11-16

### Added

- **نوبتِ همیشگی — سری تکرارشونده در یک رزرو**: فیزیوتراپیِ هر سه‌شنبه، کلاسِ هفتگی،
  آرایشِ ماهانه — رزرو دوره‌ای که هر تقویم‌ساز جدی دارد، حالا در نوبت هست. ویزارد
  چیپ‌های «تکرار هفتگی» می‌گیرد (۲، ۴، ۸ جلسه)؛ رخداد اول به اسلات واقعی‌ای می‌لنگد
  که بازدیدکننده انتخاب کرده و هفته‌های بعد از همان گریدِ روزِ هفته مشتق می‌شوند و
  می‌توانند عمداً پشتِ پنجرهٔ رزروِ پیش‌رو بنشینند — رزروِ یک سریِ دوره‌ای خودِ راهِ
  مشروعِ رزروِ دور است، در حالی که پنجره هنوز از تک‌نوبت‌های عادی نگهبانی می‌کند
  (تست هر دو طرف را ثابت می‌کند). کل سری یا همه‌چیز ثبت می‌شود یا هیچ‌چیز: تضاد در
  هفتهٔ سوم، هفته‌های یک و دو را هم برمی‌گرداند و پیامک‌های afterCommit هرگز برای
  سریِ بازگشته نمی‌سوزند. رخدادها یک series_token به اشتراک می‌گذارند و صفحهٔ پیگیری
  هفته‌های بعدی را با کدهای خودشان فهرست می‌کند. در همین مسیر تست‌های سری یک باگ واقعی
  محصول را بیرون کشیدند: resolve کد تخفیف ورودیِ بازدیدکننده را uppercase می‌کرد اما
  کدِ ذخیره‌شده به حالت اصلی‌اش می‌ماند — کدی که «welcome10» ذخیره شده بود برای
  مهمانی که «WELCOME10» می‌زد دیده نمی‌شد. حالا کدها هنگام نوشتن نرمال می‌شوند و
  یک سریِ دوره‌ای فقط یک بار کد را مصرف می‌کند؛ هفته‌های بعدی بدون سوزاندن سقفِ
  هر-کاربر، همان شکستِ قیمت را به ارث می‌برند.
- **QR در پذیرش و رسید روی کاغذ — ورود بی‌کلام**: صفحهٔ پیگیری حالا QRِ همان نشانی
  را رندر می‌کند (سمت کلاینت، data URI — img-src از قبل مجاز بود): پذیرش اسکن
  می‌کند، به صفحهٔ پیگیری می‌رسد، بدون یک کلمه حرف‌زدن. دکمهٔ چاپ همان جعبه را به
  رسید کاغذی بدل می‌کند — استایل‌های print فقط کد و QR را روی کاغذ می‌گذارند و
  ناوبری، فرم‌ها و دکمه‌ها هرگز به کاغذ نمی‌رسند. QRِ ناموفق هرگز صفحه را نمی‌شکند:
  رندر wrap شده و کدِ چاپی بدون QR هم خوانا می‌ماند.
- **یک ضربه تا تقویم گوگل — فایل ICS هم‌اتاقی پیدا کرد**: دانلود ICS تنها درِ
  تقویم بود و از بازدیدکننده می‌خواست بداند فایل .ics چیست. حالا دکمهٔ تقویم گوگل
  کنارش هست: URL قالبی که سمت سرور ساخته می‌شود — نام کسب‌وکار، خدمت، تاریخ‌های UTC،
  کد پیگیری در توضیحات و نشانی در location — پس فرمت تاریخ‌ها با تست تضمین می‌شود،
  نه با امید. کسب‌وکاری بدون نشانی به location خالی فرو می‌ریزد، هرگز به کلمهٔ null.

### Changed

- تست‌ها: ۲۶۴ → **۲۷۲** (۶ سری تکرارشونده + ۲ تقویم گوگل)؛ نسخهٔ اپ به 1.23.0 رسید.

## [1.22.0] - 2026-11-16

### Added

- **دایرکتوری که جواب می‌دهد — دسته‌بندی و جست‌وجوی سمت سرور**: بازدیدکننده
  فهرستی تخت را ورق می‌زد و امیدوار بود کسب‌وکار درست اتفاقی همان‌جا باشد. حالا
  `?q=` نام، توضیحات و آدرس را می‌گردد و `?category=` فهرست را به شش گروه بومی‌سازی‌شده
  می‌بُرد — هر ترکیب یک URL قابل‌اشتراک است و کارت هر کسب‌وکار دسته‌اش را به‌صورت نشان
  می‌پوشد. فهرست کامل پنج‌دقیقه‌ای کش می‌ماند و جست‌وجوی بازدیدکننده در حافظه فیلتر
  می‌شود؛ یک کش به‌ازای هر عبارتِ جست‌وجو خودش نقضِ معنای کش است و دایرکتوری به‌قدری
  کوچک است که فیلترکردن از خواندنِ کشِ جدید سریع‌تر تمام می‌شود. اختصاص دسته «کاریابی
  دایرکتوری» است — کارِ پلتفرم — پس کنار toggle سوپرادمین نشسته و هر تغییر کش را
  می‌شکند.
- **قرارداد API محصول شد — استوری OpenAPI 3.1 در `/docs/openapi.json`**: API توکن‌محور
  واقعی بود اما تنها مرجعش یک فایل مارک‌داون بود که آدمِ فراموش‌کار ممکن بود
  به‌روزش نکند. حالا قرارداد در یک ساختار واحد (`App\Support\ApiSpec`) زندگی می‌کند
  با دو چهره: `/docs` صفحهٔ مرجع بومی‌سازی‌شده‌ای که مستقیم از همان ساختار رندر
  می‌شود و `/docs/openapi.json` همان JSON خام برای generatorهای کلاینت — متنِ مستندات
  دیگر هرگز از کد جا نمی‌ماند. یک تست Pest هر عملیات مستند‌شده را در هر دو جهت با
  روتر مقایسه می‌کند؛ افزودن endpoint بدون مستندسازی، CI را قرمز می‌کند. لینک صفحه
  در فوترِ همهٔ مسیرهای عمومی هست و پیکربندی `app.version` — که از 1.16.0 روی بج فرود
  کهنه مانده بود — حالا خوراک فیلد نسخهٔ استوری است.
- **رزرو دوباره با یک کلیک — مسیر بازگشت از پیش پرشده**: مشتری‌ای که نوبتش تمام شد
  باید صفحهٔ کسب‌وکار را باز می‌کرد و از صفر دوباره همه‌چیز را جواب می‌داد. حالا
  صفحهٔ پیگیری برای نوبت‌های انجام‌شده دکمهٔ «رزرو دوباره» دارد که با همان خدمت و همان
  پرسنل به ویزارد می‌رسد — بازدیدکننده از شبکهٔ اسلات‌ها شروع می‌کند، نه از صفر.
  کنترلر فقط از ردیف‌های فعالِ خودِ کسب‌وکار پیش‌انتخاب می‌کند: شناسهٔ بیگانه یا
  غیرفعال بی‌سروصدا نادیده گرفته می‌شود و ویزارد خالی باز می‌شود؛ لینکِ کهنه هرگز
  خطا نیست.

### Changed

- تست‌ها: ۲۴۹ → **۲۶۴** (۷ دایرکتوری + ۴ مستندات API + ۴ رزرو دوباره)؛ بج READMEها
  به‌روز شد و پیکربندی نسخهٔ اپ از 1.16.0 به 1.22.0 رسید.

## [1.21.0] - 2026-11-15

### Added

- **جابه‌جایی نوبت بدون تماس — تغییر زمان، نه تشریفات**: تا حالا تغییر ساعتِ یک نوبت
  یعنی لغو + رزرو دوباره: تخفیف می‌مُرد، زمینهٔ پرداخت می‌مُرد و مشتری کل فرم را برای
  بار دوم پر می‌کرد — یا صرفاً زنگ می‌زد. حالا صفحهٔ پیگیری یک انتخاب‌گر جمع‌وجور دارد:
  همان اثبات مالکیتِ لغو، همان خدمت و همان پرسنل (جابه‌جایی هرگز بی‌سروصدا کسی را عوض
  نمی‌کند)، و بازه‌های آزاد برای روز انتخابی. `BookingService::reschedule()` ردیف پرسنل
  را مثل `reserve()` قفل می‌کند و علت واقعی رد را نام می‌برد (پنجرهٔ گرفته‌شده، زمان
  خارج از شبکهٔ اسلات یا فراتر از افق رزرو)؛ `SlotGenerator::generate()` پارامتر
  `$except` می‌گیرد تا جابه‌جایی با ردیفِ خودش نجنگد. لحظه‌ای که نوبت ترک می‌شود یک
  «اسلات آزادشدهٔ» واقعی است — به لیست انتظار اعلام می‌شود و پیامکِ جابه‌جایی به زبانِ
  زمانِ رزرو می‌رود.
- **دعوت‌نامهٔ نظر خودکار — حافظه‌ی تازه، نمره‌ی تازه**: نظر فقط وقتی ثبت می‌شد که مشتری
  کد پیگیری را یادش می‌ماند و خودش به صفحهٔ کسب‌وکار سر می‌زد. حالا قطعی‌شدن یک نوبت
  بلافاصله `SendReviewInviteSms` می‌فرستد: کد پیگیری + لینک کسب‌وکار + زبانِ همان رزرو.
  مهمان اصلاً اعلان درون‌برنامه‌ای ندارد — این تنها کانال اوست. جاب در لحظهٔ ارسال دوباره
  وجود نظر را چک می‌کند تا کسی که حرفش را زده مزاحم نشود و `review_invited_at` لحظهٔ
  دعوت را برای مشاهده‌پذیری مهر می‌زند.
- **انقضای هولد هم صداست — صندلیِ ناشناس مفقود نمی‌شود**: هولدِ پرداخت‌نشده پس از مهلت
  منقضی می‌شد و اسلاتش بی‌سروصدا آزاد می‌گشت؛ لیست انتظار فقط از لغوها خبر می‌شد.
  `nobat:release-expired-holds` حالا نوبت‌های آزادشده را برمی‌گرداند و هرکدام را به لیست
  انتظار اعلام می‌کند — دقیقاً مثل یک لغو.

### Changed

- موتور لیست انتظار هوشمندتر شد: `announceFreedSlotAt()` لحظهٔ آزادشده را صریح می‌گیرد،
  هر ردیف پیش از ارسال پیامک خودش را اتمیک claim می‌کند (لغوِ هم‌زمان با sweep دیگر برای
  یک اسلات دو پیامک نمی‌فرستد) و ردیفی که پرسنلِ مشخصی خواسته بود، دیگر از اسلاتِ پرسنل
  دیگران باخبر نمی‌شود.
- تست‌ها: ۲۳۰ → **۲۴۹** (۱۱ جابه‌جایی + ۵ دعوت‌نامهٔ نظر + ۳ لیست انتظار)؛ بج READMEها
  به‌روز شد.

## [1.20.0] - 2026-11-15

### Added

- **Shell صفحه‌ی واقعی را پیش‌نمایش می‌دهد — متای سرورساید به‌ازای هر مسیر**: تا
  حالا هر URL با یک `<title>` ثابت فارسی و یک og:image عمومی رندر می‌شد؛ اشتراک‌گذاری
  صفحه‌ی یک کسب‌وکار در شبکه‌های اجتماعی یعنی پیش‌نمایشِ یک بیلبورد بی‌نام — چون
  اسکرِیپرهای اجتماعی و موتورهای جست‌وجو جاوااسکریپت را اجرا نمی‌کنند و فقط شِل را
  می‌بینند. `SeoMetaCreator` (view creator روی `app.blade.php`، بعد از `SetLocale`
  یعنی زبانی و آگاه از کوکی) به‌ازای هر مسیر تصمیم می‌گیرد: صفحه‌ی کسب‌وکار با نام،
  توضیحات و عکس کاور همان کسب‌وکار پیش‌نمایش می‌شود، صفحات قانونی تایتل سندشان را
  می‌گیرند، صفحه‌ی پیگیری تایتل کد نوبت را. canonical و twitter:image اضافه شد؛
  پیش‌فرض‌ها از رشته‌های هاردکد به `lang/{fa,en,ar}/seo.php` رفتند. تایتل کلاینت‌ساید
  چهار صفحه‌ی عمومی از قبل بود — حالا شِل هم همان را می‌گوید.
- **خروجی دادهٔ کاربر — دو نیمه‌ی حریم خصوصی کامل شدند**: `/privacy` می‌گفت بازدیدکننده
  حق گرفتن داده‌هایش را دارد، اما تنها کنترلِ سلف‌سرویس حذف حساب بود؛ قولِ بدون
  مکانیسم شعار است. `GET settings/data-export` (auth) داده‌های خودِ کاربر را به‌صورت
  فایل JSON دانلودی برمی‌گرداند: حساب + کسب‌وکارهایش با خدمات، پرسنل، ساعات کاری و
  نوبت‌ها (شامل اطلاعات تماس مشتریانشان که مالک به‌عنوان پردازنده‌ی داده نگه می‌دارد).
  کوئری با `owner_id` محدود است — داده‌ی مالکِ دیگر هرگز وارد فایل نمی‌شود. دکمه‌ی
  سه‌زبانه در تنظیمات، کنار کارت حذف حساب نشست؛ جفتِ «داده‌ات را ببر یا پاکش کن»
  کامل شد.
- **دستور پشتیبان‌گیری واقعی — `php artisan nobat:backup`**: رانبوک به اپراتور
  می‌گفت با `sqlite3 .backup` دستی بکاپ بگیرد؛ در کانتینر دمو حتی CLI برق‌آسا نصب
  نیست، پس عملاً هیچ‌کس هرگز اجراش نمی‌کرد. حالا خود محصول با `VACUUM INTO`
  snapshot سازگار می‌گیرد (حتی وسط نوشتن‌های در جریان — همان تضمینی که `cp` ساده
  ندارد)، روی فایل حاصل `PRAGMA integrity_check` اجرا می‌کند، با `--keep=N`
  نگهداری می‌کند و درایور غیر-SQLite را با ارجاع به رانبوک رد می‌کند. تست‌ها فایل
  تولیدی را با یک PDO تازه باز می‌کنند — دقیقاً مثل بازیابی واقعی؛ اجرای واقعی روی
  دیتابیس dev فایل ۳۳۲KB با integrity ok داد. cron نمونه و تمرین بازیابی به
  رانبوک اضافه شد.

### Changed
- تست‌ها: ۲۲۰ → **۲۳۰** (۴ متای شِل + ۳ خروجی داده + ۳ پشتیبان‌گیری)؛ بج READMEها
  به‌روز شد. `storage/app/backups` به `.gitignore` رفت.

## [1.19.0] - 2026-11-14

### Added

- **ضربان زمان‌بند و endpoint سلامت واقعی**: تا حالا فقط `/up` فریم‌ورک بود که
  فقط زنده‌بودن PHP را نشان می‌دهد. حالا `GET /health` دیتابیس، قابلیت نوشتن
  storage، اتصال صف و — مهم‌تر از همه — **فاصله‌ی آخرین اجرای زمان‌بند** را JSON
  برمی‌گرداند. دو دستور زمان‌بندی‌شده (`release-expired-holds` و `send-reminders`)
  در هر اجرا ضربان خود را در cache می‌نویسند؛ مرگِ cron یا worker دیگر بی‌لاگ
  نیست: `scheduler.healthy` در `/health` false می‌شود و مونیتور uptime می‌تواند
  هشدار بدهد. دیتابیس پاسخ ندهد، پاسخ 503 است تا load balancer نمونه را از
  گردش خارج کند. `healthCheckPath` در `render.yaml` به همین endpoint تغییر کرد.
- **زمان‌بند در کانتینر دمو راه افتاد**: `demo-entrypoint.sh` فقط serve می‌کرد —
  یعنی یادآوری پیامکی و آزادسازی خودکار هولدِ پرداخت‌نشده در دمو **هرگز اجرا
  نمی‌شد** و هیچ پیام خطایی هم وجود نداشت. حالا `schedule:work` پیش از
  وب‌سرور پس‌زمینه بالا می‌آید (`NOBAT_SCHEDULER=false` برای خاموشی) و لاگش در
  `storage/logs/scheduler.log` است.
- **صفحات حقوقی**: `/privacy` و `/terms` با محتوای واقعی و دقیق به سه زبان از
  `lang/{fa,en,ar}/legal.php` رندر می‌شوند — نه بیلبورد خالی. متن‌ها همان چیزی
  را توضیح می‌دهند که کد واقعاً انجام می‌دهد (چه داده‌ای جمع می‌شود، درگاه
  پرداخت و پیامک به‌عنوان اشخاص ثالث، کوکی‌ها، حقوق حذف). ستون «مستندات» با
  لینک هر دو به فوتر عمومی اضافه شد.
- **رانبوک عملیات** (`docs/OPERATIONS.md`): سه راه اجرای زمان‌بند (cron /
  worker / کانتینر)، راه‌اندازی صف با درایور database، پشتیبان‌گیری SQLite با
  `.backup` و تمرین بازیابی، هشدار با کانال Slack بدون وابستگی، چک‌لیست go-live
  با ۱۱ قلم و نقشه‌ی مقیاس‌پذیری. `docs/DEPLOYMENT.md` به آن لینک داد و دو قلم
  (زمان‌بند و `/health`) به چک‌لیست go-live اضافه شد.

### Changed
- تست‌ها: ۲۱۴ → **۲۲۰** (۳ تست سلامت + ۳ تست صفحات حقوقی)؛ بج READMEها به‌روز شد.

## [1.18.0] - 2026-11-14

### Fixed

- **کلید throttle حالا هویت واقعی بازدیدکننده است، نه سرور لبه‌ی پراکسی**: زنجیره‌ی
  دمو Cloudflare → Render است و ترمیناتور Render مقدار `X-Forwarded-For` را بازنویسی
  می‌کند — اولین آی‌پیِ آن سرورِ لبه‌ی Cloudflare است که بین درخواست‌ها می‌چرخد. با
  `trustProxies('*')` لاراول همان آی‌پیِ چرخان را «مشتری» می‌گرفت و باکت‌های
  rate-limit بین سرورهای لبه پخش می‌شد؛ راستی‌آزمایی زنده نشان داد `x-ratelimit-remaining`
  در پست‌های متوالی بین ۴ و ۳ نوسان می‌کند — یعنی دیوارِ «۵ در دقیقه» هرگز شکل نمی‌گرفت.
  middleware جدید `PinClientIpFromProxyHeaders` در انتهای استک سراسری (بعد از
  `TrustProxies`، قبل از هر خواندنِ `ip()`) وقتی `CF-Connecting-IP` معتبر (v4 یا v6)
  حاضر است، `X-Forwarded-For` را دقیقاً روی همان مقدار می‌بندد؛ ترافیک مستقیم بدون این
  هدر دست‌نخورده رد می‌شود. تست یکپارچه‌اش حالتِ خرابیِ پروداکشن را بازسازی می‌کند: پنج
  درخواست با لبه‌های چرخان و یک بازدیدکننده، یک باکت مشترک می‌سازند و ششمین درخواست 429 می‌گیرد —
  با غیرفعال‌کردن middleware همان تست می‌شکند.
- **لاگین تنها endpoint احراز هویت بدون throttle بود**: register و reset و
  forgot-password و verify-email همه دیوار داشتند، اما `POST /login` — همان که
  هدف اصلی credential stuffing است — بی‌دیوار بود و اسکریپت مهاجم می‌توانست با سرعت
  خط آن را بکوبد. حالا `throttle:5,1` دارد؛ پنج تلاشِ ناموفق شمرده می‌شود و ششمین با 429 رد می‌شود.

### Changed

- بج «tests» در هر دو README از ۲۰۷ به **۲۱۴** به‌روز شد (۶ تستِ جدید برای
  resolution آی‌پی و ۱ تست برای دیوارِ لاگین).

## [1.17.0] - 2026-11-14

### Added

- **بسته‌ی صحت چندزبانه (i18n correctness)**: ستون `locale` به `appointments` و
  `waitlist_entries` اضافه شد. `BookingService::reserve` و `WaitlistController::store`
  زبان فعالِ request را روی رکورد pin می‌کنند. `SendReminders` (که روی queue worker
  اجرا می‌شود و `app()->getLocale()` در آن همیشه `fa` است) و `WaitlistPromotion`
  هرکدام از زبان رکورد می‌خوانند — تا یک مشتری انگلیسی/عربی که در همان زبان رزرو
  کرده، پیامک یادآوری و ارتقای لیست انتظار را در همان زبان دریافت کند. نوتیفیکیشن
  `AppointmentStatusChanged` هم زبان appointment را به‌جای زبان actor می‌گیرد.
- **فلش `info` به Inertia share اضافه شد**: تا حالا `HandleInertiaRequests::share()`
  فقط `success` و `error` را share می‌کرد؛ `info` (که برای «n مشتری مطلع شد» و
  «در لیست انتظار هستید» استفاده می‌شود) هرگز به فرانت نمی‌رسید — حالا می‌رسد.
- **بسته‌ی امنیت (security hardening)**:
  - **URL امضادار برای فاکتور**: `/invoices/{number}` دیگر عمومی نیست؛
    `InvoiceController::show` امضای معتبر می‌خواهد. `BookingController::show` و
    `PaymentConfirmed` مایل signed URL با انقضای ۷ روز صادر می‌کنند.
  - **Rate limit روی auth POSTها**: register/reset-password با `throttle:10,1`،
    forgot-password با `throttle:5,1` (سخت‌گیرانه‌تر برای جلوگیری از email
    enumeration).
  - **CSP nonce-based**: هر request یک nonce 16-byte تولید می‌شود، به Vite helper
    و `@routes` پاس داده می‌شود؛ `script-src 'self' 'nonce-…' 'strict-dynamic'`،
    `object-src 'none'`، `frame-ancestors 'self'`.
- **بسته‌ی مشاهده‌پذیری (payment observability)**: structured logging در
  `ZarinPalGateway::request/verify/refund` و در تمام outcomes از
  `PaymentService::handleCallback` (not_found، busy، canceled_by_user،
  already_settled، verify_failed، expired_refunded، paid) و در `refundPayment`.
  `ReleaseExpiredHolds` هم `Log::info` می‌زند نه فقط `$this->info`.
- **تست‌های جدید** برای همه‌ی موارد بالا.

### Changed

- `BusinessController::show` دیگر سه کوئری جدا روی `approvedReviews` نمی‌زند —
  eager-load با `loadAvg/loadCount` جایگزین شد (یک کوئری per relation).
- `BookingController::show` با eager-load `review` و `payments.invoice` از چند
  lazy query (review()->exists()، review()->first()، $payment->invoice?->number)
  خلاص شد.
- `WaitlistController::store` با `Cache::lock` per-phone-per-date روی check-then-insert
  سریالیزه می‌شود — دو درخواست همزمان دیگر دو ردیف تکراری نمی‌سازند.
- `docs/ARCHITECTURE.md` با ۸ بخش جدید (پرداخت و idempotency، چندزبانه و RTL،
  گالری، لیست انتظار، SEO، خطا، امنیت، مشاهده‌پذیری) به‌روز شد.
- README و README.en از «Laravel 13» به «Laravel 12» اصلاح شدند؛ شمارش تست از ۱۳۱
  به ۱۸۸ درست شد؛ v1.16 به نقشه‌راه انگلیسی اضافه شد.

## [1.16.0] - 2026-11-13

### Added

- **سِرِمونی ممیزی — کمبودهای پیدا شده با ممیزی کامل، تکمیل شدند:**
  - **پنل‌های چندزبانه واقعاً سه‌زبانه شدند**: کاتالوگ‌های `lang/en/{auth,validation}.php`
    اضافه شدند (پیش‌تر انگلیسی‌زبان‌ها پیام‌های ورود را فارسی می‌خواندند) و
    کلیدهای `confirmed`، `image`، `mimes` و `max.file` به fa/ar اضافه شدند
    (ثبت‌نام و آپلود گالری برای کاربر فارسی/عربی پیام انگلیسی می‌گرفتند).
  - چهار صفحه‌ی auth (فراموشی/بازیابی/تأیید رمز/تأیید ایمیل) ترجمه شدند — تا
    حالا فارسی یا انگلیسی هاردکد بودند.
  - صفحات خطای برندشده (۴۰۳/۴۰۴/۴۱۹/۵۰۰/۵۰۳) عربی گرفتند و `lang`/`dir` را
    مستقیم از کوکی می‌خوانند (صفحات خطا از گروه middleware عبور نمی‌کنند)؛
    فاکتور چاپی هم کلیدهای `invoice.*` و وضعیت ترجمه‌شده گرفت.
  - **پیامک‌ها، ایمیل پرداخت و نوتیفیکیشن دیتابیس چندزبانه شدند**: زبان هنگام
    dispatch ثبت و روی worker بازگردانی می‌شود؛ متن‌ها از کلیدهای `sms.*` و
    `mails.*` می‌آیند.
  - هفت پیام اعتبارسنجی هاردکد، توضیح درگاه پرداخت، علت رفاند خودکار و فلش
    «لینک بازیابی» به کاتالوگ‌ها رفتند.
- **SEO**: `sitemap.xml` (لندینگ + هر کسب‌وکار فعال، کش ۳۰ دقیقه)، `robots.txt`
  داینامیک با اشاره‌گر Sitemap و بستن پنل‌ها، و **JSON-LD** — `LocalBusiness`
  روی پروفایل کسب‌وکار (عکس جلد، خدمات، امتیاز تجمیعی) و `WebSite` روی لندینگ.
- **لغو خودخدمتی مهمان**: از صفحه‌ی نوبت، مهمان با شماره‌ای که رزرو با آن
  انجام شده نوبت آینده‌ی پرداخت‌نشده را لغو می‌کند (`throttle:5,1`، نرمال‌سازی
  ارقام فارسی/عربی، نوبت‌های پرداخت‌شده مستثنی).
- **ارتقای خودکار لیست انتظار**: لغو نوبت (مدیر یا مهمان) به همه‌ی منتظرهای
  همان کسب‌وکار/خدمت/روز پیامک با لینک رزرو می‌زند و وضعیت را «مطلع شده»
  می‌کند — سرویس `WaitlistPromotion` + جاب `SendWaitlistPromoSms`.
- **پاسخ مدیر به نظرات**: ستون‌های `reply`/`replied_at`، ویرایشگر پاسخ در پنل،
  و حباب «پاسخ کسب‌وکار» زیر نظر مشتری در صفحه‌ی عمومی.
- **مرتب‌سازی گالری**: endpoint و دکمه‌های بالا/پایین در پنل؛ نمای عمومی
  عکس جلد را اول نمایش می‌دهد.
- **پیگیری نوبت از فوتر**: فرم کد پیگیری مستقیم به صفحه‌ی نوبت می‌رود.
- **تاگل سریع دارک‌مود** در هدر پنل و منوی عمومی (قبل از این فقط در تنظیمات).
- نسخه‌ی اپ از `config('app.version')` به فرنت share می‌شود (بج لندینگ دیگر
  هرگز کهنه نمی‌شود).

### Changed

- مشتری‌ها، لیست انتظار و نظرات پیجینیت شدند (۱۵/۲۰/۲۰): کتاب مشتری‌ها با یک
  کوئری SQL گروه‌بندی‌شده (زیرکوئری‌های correlated نام و آخرین مراجعه را حفظ
  می‌کنند) و جستجوی مشتری سمت سرور با پارامتر `q`؛ فیلتر تب‌های لیست انتظار
  هم سمت سرور شد.
- محور نمودار تحلیل برای عربی «م»/«ألف» می‌گیرد؛ PRODID خروجی iCal با زبان
  فعال امضا می‌شود.

### Removed

- `AuthSplitLayout.vue` — لایه‌ت بی‌استفاده با متن هاردکد فارسی.
- `public/robots.txt` استاتیک — نسخه‌ی داینامیک جایگزین شد.

### Tests

- **۱۵۳ → ۱۸۸ تست Pest** (طبق لاگ CI): پارتی کاتالوگ‌های auth/validation در سه زبان، خطای
  ورود انگلیسی، صفحات خطای سه‌زبانه، فاکتور دو‌زبانه، پیامک/ایمیل چندزبانه با
  ثبت زبان هنگام dispatch، sitemap/robots، لغو مهمان (۶ سناریو)، ارتقای لیست
  انتظار (۳ سناریو)، پاسخ به نظر (۵ سناریو)، مرتب‌سازی گالری (۳ سناریو) و
  پنجره‌ی پیجینیشن مشتری‌ها.

## [1.15.0] - 2026-11-13

### Added

- **Trilingual experience — Arabic joins as the third language (FA: عربی به‌عنوان زبان سوم)**:
  - A complete Arabic catalog: ~500 frontend keys (`resources/js/locales/ar.json`),
    111 server keys (`lang/ar.json`) plus native Laravel `lang/ar/{auth,validation}.php`.
  - Arabic reads **right-to-left** like Persian — the sidebar side, the
    date-picker chevrons and the hero gradient now flip on "is English"
    instead of "is Persian", so fa and ar share the RTL layout.
  - Arabic formatting: Gregorian dates with Arabic month names and Latin
    digits (`ar-u-nu-latn`, deliberately avoiding the Hijri calendar that
    `ar-SA` would pick), prices as "X تومان", Latin digits everywhere.
  - The language switcher (navbar + panel header) offers **العربية** with a
    compact "ع" badge; `POST /locale` and `SetLocale::SUPPORTED` accept `ar`.
  - The Jalali calendar stays for fa; ar keeps it too with Arabic-script
    month names (فروردين…إسفند) so booking slots read naturally.
- `LocaleTest` grows from 9 to 11 cases: the Arabic RTL shell (`lang="ar"`
  `dir="rtl"`), Arabic flash messages end-to-end, `ar` cookie persistence and
  **three-way catalog parity** (501 keys in each frontend catalog, 111 in
  each server catalog).
- The landing badge now shows the real version (1.15.0 — it was stale at
  1.13.0).

## [1.14.0] - 2026-11-13

### Added

- **Bilingual management panels (FA: پنل‌های چندزبانه)** — the whole dashboard
  now follows the visitor's language, not just the public pages:
  - All 14 dashboard pages, the 3 settings pages, the sidebar navigation and
    the shared status badges are wired to vue-i18n through a fresh `panel.*`
    namespace (~200 keys per language).
  - The language switcher now lives in the panel header too — one click
    flips every label, breadcrumb, dialog, empty state and confirmation.
  - Numbers, prices, dates and status names are locale-aware across the
    panels: Persian keeps the Jalali calendar and Persian digits, English
    switches to Gregorian dates with Latin digits and Toman prices.
  - Server side, every flash message, validation error and report export
    (CSV headers and the Excel sheet title) is translated through the
    `lang/{fa,en}.json` files — including appointment status labels via the
    new `localizedStatusLabel()` model helper.
- **Business photo gallery (FA: گالری تصاویر)** — photos of businesses:
  - A `business_photos` table with caption, sort order and a single cover
    photo; the manager panel gains a Gallery page with multi-file upload
    (up to 6 per batch, max 12 photos, 2 MB each), caption editing, cover
    switching and delete — the cover role is never left empty.
  - The public business page renders the photos as a responsive grid with
    a lightbox (prev / next / close) on top of the existing booking flow.
  - Photos stream through a `media/gallery/{photo}` route with long-lived
    cache headers — `artisan serve` hosts cannot serve the /storage symlink
    statically, so Laravel hands out the bytes itself.
  - Demo content ships with 10 real photos (5 per demo business) seeded
    from the repository at boot, so the demo shows the gallery instantly.
- Covered by 8 new Pest tests (150 total): upload with the auto-cover
  first photo, the twelve-photo ceiling, upload validation (mime + size),
  caption and cover switching, cover handover on delete, manager/staff/
  stranger lockout, the public payload and an English dashboard flash.

## [1.13.0] - 2026-11-12

### Added

- **Customers panel (FA: پنل مشتری‌ها)** — the last open roadmap item:
  the customer book of every business, one row per customer phone,
  aggregated live from the appointment history — lifetime spend, the
  completed/cancelled/no-show split and the no-show rate counted over
  the visits that actually reached the chair.
  - `dashboard/Customers` lists the book sorted by lifetime spend with
    total/active/revenue stat cards, a client-side name/phone search
    and a «پروفایل» link per row.
  - `dashboard/CustomerProfile` opens per customer: four lifetime
    stats plus the full appointment history (newest first) with
    service, staff, status badge and per-visit price.
  - Routes `GET /customers` and `GET /customers/{phone}` (digits-only
    constraint) sit in the auth group scoped by `ResolvesManagedBusiness`
    — a manager only ever sees their own book; staff users and
    strangers get a 404.
- Covered by 5 new Pest tests (142 total): the aggregation maths
  (1,000,000 vs 500,000 spend and a 50% no-show rate), per-business
  scoping across two businesses, the profile history order, a 404
  for a phone that never booked and staff exclusion.

## [1.12.0] - 2026-11-12

### Added

- **Branded error pages (FA: صفحات خطای برندشده)**: RTL Persian pages for
  403/404/419/500/503 in the Nobat dark style — self-contained blade
  with inline styles and no vite dependency, so they render even when
  the app or its assets are down; bilingual messages with a back-to-home
  action.
- **Security header set**: `nosniff`, `SAMEORIGIN` framing, strict
  referrer policy and a camera/microphone/geolocation permissions
  policy now ship on every web and API response; HSTS
  (`max-age=31536000; includeSubDomains`) joins only in production.
  Headers are stamped by a small shared helper called both from
  middleware (normal responses) and the exception responder hook
  (rendered error pages and aborted JSON), so even a 401 carries them.
- **SEO / social sharing**: the blade shell gains a meta description,
  OpenGraph and Twitter card tags with a 1280x640 preview image served
  from `public/social-preview.png`; the landing and every public
  business page inject per-page description and OG tags through Inertia
  Head.
- **Hardened demo**: the Render blueprint sets
  `SESSION_SECURE_COOKIE=true` and `LOG_LEVEL=warning`, the demo image
  precompiles config/routes/views at boot for a faster cold start, and
  the lockfile was refreshed by `npm audit fix` (23 vulnerabilities
  down to 5 moderate dev-tooling advisories, 0 critical/high).
- Covered by 3 new Pest tests (137 total): the header set on web
  responses with HSTS correctly absent in testing, headers surviving on
  the branded 404 page and the API 401 JSON.

### Changed

- **CI consolidated**: the three overlapping workflows (`ci.yml`,
  `tests.yml`, `lint.yml`) ran the same Pint + Pest + build suite with
  slight variations — everything now lives in the single canonical
  `ci.yml` pipeline, halving the per-commit CI footprint.
- **Branded composer metadata**: the package name/description moved
  from the `laravel/vue-starter-kit` skeleton placeholder to the Nobat
  identity with keywords and support links.

## [1.11.0] - 2026-11-12

### Added

- **Excel export (FA: خروجی اکسل)**: the reports route now accepts
  `?format=excel` and streams a real `.xlsx` workbook — same header and
  rows as the CSV export (code, customer, phone, service, staff, time,
  status, amount) with numeric amount cells, a bold header row and a
  right-to-left sheet view; the reports page gains a «خروجی اکسل»
  button next to the existing CSV one.
- **Zero-dependency OOXML writer**: `App\Support\XlsxWriter` builds the
  workbook by hand — a stored (uncompressed) ZIP archive with per-part
  crc32 checksums written via `pack()`, so no ext-zip and no composer
  package are needed; `composer.json` stays untouched.
- Covered by 3 new Pest tests (134 total): the archive-integrity test
  walks every local header, recomputes each part's crc32, verifies the
  central-directory offsets and the end-of-central-directory record,
  checks the Persian content and numeric cells of the sheet, an
  empty-range export still yields a valid header-only workbook and the
  CSV default behaviour is kept.

## [1.10.0] - 2026-11-12

### Added

- **Public JSON API (FA: API عمومی)**: a Bearer-token REST API under
  `/api/v1` — `POST /api/v1/tokens` exchanges email + password for a
  token (throttled 10/min), then `GET /me`, `GET /businesses` and the
  paginated, filterable `GET /appointments` (status, date, per_page and
  a super-admin `business_id` scope) serve programmatic access with the
  exact same tenancy rules as the dashboard (`visibleTo`).
- **Hashed tokens**: only the sha-256 of each token is stored
  (`api_tokens` table with `last_used_at`), so a leaked database can
  never mint a token; the plain token is returned exactly once at
  creation. `DELETE /api/v1/tokens/{id}` revokes with immediate effect
  and refuses foreign tokens with 404.
- **Token management page**: a new dashboard page (کلیدهای API) creates
  and revokes tokens without touching curl — the plain token is shown
  once in a copy-once callout and never persisted server-side.
- **Zero new dependencies**: the token layer is a small middleware plus
  a hashed table rather than a new composer package, keeping
  `composer install` reproducible.
- **API reference**: `docs/API.md` documents every endpoint with curl
  examples, filters, pagination shape and status codes (Persian).
- Covered by 10 new Pest tests (131 total): token issuance and
  hash-only storage, wrong credentials, the bearer guard, the profile
  endpoint, tenancy-scoped listings with filters and pagination,
  business counts, revocation plus the 401-after-revoke guarantee,
  foreign-token refusal and the `last_used_at` refresh.

## [1.9.0] - 2026-11-12

### Added

- **SMS reminders (FA: یادآوری پیامکی)**: the hourly scheduled
  `nobat:send-reminders` command queues a reminder for every confirmed
  appointment starting within the next 24 hours (widenable with
  `--within`), delivered over the existing SMS driver architecture —
  the `log` driver in development and the real Kavenegar gateway in
  production.
- **Send first, stamp after**: `SendReminderSms` stamps
  `appointments.reminder_sent_at` only once the gateway accepted the
  message, so a failed send stays queued for retry and a successful one
  is never repeated; cancelled or already-reminded appointments are
  skipped on every retry.
- **Kill switch**: `NOBAT_SMS_REMINDERS=false` silences the reminders
  without touching the driver configuration.
- Covered by 4 new Pest tests (121 total): window scoping with
  pending / out-of-window / already-reminded skips, delivery plus the
  reminder stamp through the log driver, the no-stamp-on-failure
  guarantee against a rejecting gateway, and the disabled flag.

## [1.8.0] - 2026-11-12

### Added

- **Analytics dashboard (FA: تحلیل)**: a new `/analytics` page beside the
  reports with six KPI cards — range revenue, bookings, completion and
  cancellation rates, average ticket, unique customers and the average
  approved rating — each with a delta badge that compares the window
  against the equal-length previous one.
- **Discount-aware revenue**: analytics revenue subtracts the granted
  `DiscountRedemption` amounts from completed bookings, so a discounted
  month no longer overstates income (the classic reports keep their
  historical gross semantics).
- **Daily booking and revenue trends** with 7/30/90-day range switching,
  rendered by a reusable `BarSeries` component — no chart library added,
  the bundle stays lean.
- **Peak hours and a weekday × hour heatmap**: the busiest start hours of
  the range plus a demand grid over the Iranian week (شنبه … جمعه), so
  managers can see exactly when to add staff.
- **Top services by revenue** (previously ranked by count) and the status
  breakdown, scoped to the managed business — super-admins may inspect
  any business via `?business_id=`.
- Covered by 7 new Pest tests (117 total): KPI maths with a redeemed
  code, previous-period deltas including the no-baseline null case,
  cross-business isolation, range fallback/90-day support, the
  super-admin scope and the guest guard.

## [1.7.1] - 2026-11-12

### Fixed

- **Working hours board (500)**: `working_hours.starts_at/ends_at` are `time`
  columns that Eloquent hydrates as plain strings, so the board's
  `->format('H:i')` presentation threw "call to a member function format()
  on string" and the page 500'd on every render — a latent bug since v1.1
  that no test and no screenshot ever exercised. The model now casts both
  columns as `datetime:H:i` Carbon instances (the slot generator keeps
  parsing them transparently), and the board is covered by regression
  tests: the manager render (windows, weekday names, H:i strings) and the
  guest redirect.

## [1.7.0] - 2026-11-11

### Added

- **Discount codes (FA: کد تخفیف)**: managers create percent or fixed-amount
  codes per business with a minimum purchase threshold, a total usage cap,
  a per-customer (phone) cap, an activation window and one-click
  enable/disable from the new dashboard page. Codes that have already been
  redeemed cannot be deleted — they are deactivated instead, keeping the
  billing history intact.
- **Live code validation**: while booking, the wizard checks the code
  against `GET /b/{slug}/discount` (throttled) and shows the discount
  amount plus the payable price before submission; the booking request
  validates the code again server-side inside the reservation transaction.
- **Discount-aware billing**: the issued invoice and the gateway payment
  both use the discounted amount (`service price − discount`), and the
  invoice snapshot records the code and the amount granted for audit.
  Redemptions are recorded atomically with a `lockForUpdate` on the code
  row, so two concurrent bookings can never overspend a capped code.
- **Waitlist (FA: لیست انتظار)**: when the slot grid of a day is empty, the
  wizard swaps the "no slots" message for a waitlist panel — the customer
  leaves name + phone for that service/day and the request lands in the
  business's waitlist (JSON `201`/`409` responses keep the inline UX
  redirect-free; duplicate signups for the same day/service/phone are
  rejected with a friendly message).
- **Waitlist board**: a new dashboard page lists entries grouped by status
  (waiting / notified / booked / closed) with per-status counters; managers
  mark entries as "customer reached", cancel or delete them, and entries
  close automatically as *booked* when the same phone actually books that
  day.
- **Freed-slot hint**: cancelling an appointment now surfaces a flash
  message telling the manager how many waitlisted customers are hoping for
  that exact day/service — nothing gets notified automatically, the
  decision stays with the business.
- **Bilingual strings**: every new public-facing message (discount errors,
  waitlist confirmations, duplicate/limit notices) ships in `lang/fa.json`
  and `lang/en.json`; the wizard and confirmation UI use the vue-i18n
  catalogs in both locales with RTL/LTR-safe layouts.

### Tests

- 19 new Pest tests (108 total): code validation and every limit, invoice
  math with discounts, live-check endpoint, manager CRUD/toggle/delete
  guards, waitlist signup/duplicate/past-date validation, the board's
  authorization scope, freed-slot hints and auto-closing booked entries.

## [1.6.0] - 2026-11-11

### Added

- **Bilingual experience (FA/EN)**: a navbar language switcher (and one on the
  auth pages) flips the whole public experience between Persian and English —
  landing, directory cards, business page, the 4-step booking wizard, the
  confirmation page and the login/register forms. The choice persists in a
  one-year `nobat_locale` cookie and survives hard reloads.
- **True RTL ↔ LTR flip**: the Blade shell now renders `lang`/`dir` from the
  cookie (`dir="rtl"` for fa, `dir="ltr"` for en), directional icons (hero
  arrows, calendar chevrons) mirror themselves and the English shell
  self-hosts the **Inter** typeface while Persian keeps Vazirmatn.
- **Locale-aware formatting**: a `useLocale()` composable unifies numbers,
  prices and dates — Persian keeps Jalali dates with Persian digits and
  «تومان», English switches to Gregorian dates, Latin digits and Toman.
- **Server-side i18n**: `SetLocale` middleware + `LocaleController`
  (`POST /locale`) drive Laravel's translator — booking/review/payment flash
  messages and slot-availability exceptions resolve from `lang/fa.json` and
  `lang/en.json`; the shared Inertia payload exposes the resolved locale.
- **vue-i18n 11** on the frontend with typed `fa`/`en` message catalogs
  (`resources/js/locales/`) and sync tests that fail the build if the two
  catalogs ever drift apart.
- **English Jalali calendar**: the booking date-picker transliterates Jalali
  month names (Farvardin…Esfand) with Latin digits in English; the month
  navigation chevrons also follow the reading direction (this fixes the
  previously inverted fa chevrons).

### Fixed

- The landing badge's Sparkles icon never rendered (unregistered import) —
  now it shows; the two Persian date-picker chevrons acted inverted to their
  labels — navigation is now consistent in both locales.

### Notes

- The dashboard stays Persian-first in this release; the public/booking
  experience is fully bilingual. See `docs/INTERNATIONALIZATION.md` for
  adding a new language.

## [1.5.0] - 2026-11-11

### Added

- **Ratings & reviews**: customers rate a completed booking (1–5 stars) from its
  tracking page; reviews land in a moderation queue and go public — star average
  on the directory cards and business header, the full review list on the
  business page — only after manager approval. One review per appointment is
  enforced by a unique index, and only completed bookings can be rated.
- **Review moderation for managers**: new dashboard page (`/reviews`) with
  approve/delete actions, pending-vs-approved stats and the average rating; the
  cached directory is invalidated on every moderation change.
- **Manager reports with CSV export**: new report page (`/reports`) with a
  custom date range — totals, completed/cancelled counts, range revenue, a
  status breakdown, top services and a daily trend chart — plus a one-click CSV
  download (UTF-8 BOM so Excel renders Persian correctly).
- **iCal export**: `GET /bookings/{code}/ics` streams a VEVENT calendar file
  (UTC timestamps, RFC-escaped text, cancelled bookings marked `CANCELLED`) so
  customers can save the appointment to any calendar app; linked from the
  confirmation page as «افزودن به تقویم».
- **PWA**: installable web app — `manifest.webmanifest` (fa, RTL, standalone,
  theme #171717), generated brand icons (192/512 + maskable) and a conservative
  service worker that cache-serves only content-hashed build assets; registered
  in production builds.
- **Demo content**: seeded reviews for completed demo appointments — mostly
  approved with realistic Persian comments, one left pending for the moderation
  flow.

### Fixed

- The demo link in both READMEs was malformed (`ttps://…` without the opening
  bracket); the English README's tests badge was stale (49 → 81).

## [1.4.1] - 2026-11-09

### Fixed

- **Booking confirmation page rendered without a way to pay**: the
  pending-payment call-to-action required an existing payment record, but
  gateway sessions are only created when the customer initiates payment —
  so a fresh pending booking showed a warning with no payment button. The
  CTA now gates on the appointment status alone and falls back to the
  service price for the amount label.
- **`faDate` threw `ReferenceError: jalaali is not defined`**: any page
  rendering a Jalali date through `faDateTime` (booking confirmation card,
  appointments table) silently collapsed to an empty card. It now calls the
  bundled `@/lib/jalali` helper instead of a leftover global.

### Changed

- Landing version badge bumped to 1.4.0; README gallery and demo
  screenshots refreshed (new landing, payment confirmation with deadline,
  real ZarinPal sandbox gateway).

## [1.4.0] - 2026-11-09

### Added

- **Online payment cycle with the ZarinPal gateway (sandbox-first)**
  across four building blocks:
  - **Model**: `payments`, `invoices` and `refunds` tables (amount, status,
    gateway `authority`, unique `idempotency_key`, masked card, JSON
    snapshots) plus `payment_required` / `payment_deadline_minutes` on
    businesses and `payment_expires_at` on appointments
  - **Gateway**: `PaymentGateway` contract behind a driver manager —
    `zarinpal` (v4 API, sandbox base URL, amounts converted Toman → Rial,
    10s timeout with retries, loud failures) and a zero-network `log`
    gateway for local dev and tests
  - **Flow**: `GET /payments/{code}/pay` starts a session, the customer is
    redirected to `StartPay/{authority}`, and `GET /payments/callback`
    verifies. **Triple idempotency** — UNIQUE index on `authority`, an
    atomic per-payment cache lock, and a settled-status guard re-checked
    inside the transaction — so a repeated webhook can never record a
    second paid payment (code 101 is treated as an idempotent success)
  - **Lifecycle**: unpaid bookings hold their slot until the deadline
    (`nobat:release-expired-holds` scheduled every minute), money arriving
    late is auto-refunded, cancelling a paid appointment reverses the
    payment, and a printable invoice page renders the frozen issue-time
    snapshot
- Payment confirmation goes out via SMS (`SendPaymentSms`) and email
  (`PaymentConfirmed` notification)
- Payment flow sequence diagram in the README and the new `payments` /
  `invoices` routes documented for the demo (Render blueprint now ships
  ZarinPal sandbox variables)

### Changed

- Bookings at payment-required businesses start as `pending_payment` with
  a slot hold instead of `pending` — existing unpaid flows are unchanged
- The demo clinic (کلینیک سلامت پارس) requires online payment so the live
  demo exercises the real sandbox gateway end-to-end

## [1.3.0] - 2026-11-08

### Changed

- **Redesigned public landing page**: gradient hero with live stats pulled
  from the database (active businesses, services, staff), a feature grid of
  the six core capabilities, a three-step booking walkthrough, a decorative
  booking preview card, and a dark demo panel listing the seeded accounts
  with a one-click sign-in shortcut
- Public layout: sticky header with backdrop blur and a rebuilt footer with
  brand summary, project links (GitHub, deployment guide, changelog) and
  the MIT license notice
- Anchor links scroll smoothly (`scroll-smooth` on the document root)

## [1.2.0] - 2026-11-08

### Added

- **One-click demo sign-in**: demo deployments (`NOBAT_DEMO=true`, set by
  the Render demo image) list the seeded accounts on the login page and
  sign the visitor in with a single click
- **Kavenegar SMS driver** behind the existing `SmsDriver` contract —
  `NOBAT_SMS_DRIVER=kavenegar` plus `KAVENEGAR_API_KEY` /
  `KAVENEGAR_SENDER`; 10s timeout, two retries, loud failures so the
  queued job applies its own backoff, and a gateway test suite
  (`tests/Feature/SmsGatewayTest.php`, fake HTTP)
- `docs/DEPLOYMENT.md`: local, Docker Compose, Render demo and Kavenegar
  setup with a go-live checklist

### Changed

- `.env.example` defaults to the Nobat identity (name `Nobat`, `fa`
  locale, `fa_IR` faker) instead of the starter-kit values
- README demo section documents the one-click sign-in and links the new
  deployment guide

## [1.1.0] - 2026-11-06

### Fixed

- **RTL layout**: dashboard sidebar now renders on the reading-start side
  (`side="right"`) instead of the physical left edge; the 264px dead margin
  on the main content and the clipped 7-day chart are gone
- Mobile navigation sheet slides in from the right to match RTL reading order
- Generated URLs always use `https` in production: trust all proxy
  `X-Forwarded-*` headers and force the scheme, so browsers never see
  mixed content on TLS-terminated PaaS hosts (Render demo was rendering
  a blank page because assets were referenced over `http://`)
- Demo Dockerfile normalises `RENDER_EXTERNAL_URL` to https for `APP_URL`
- Demo / production images bumped to PHP 8.4 to match the locked symfony
  v8.1.x packages (`composer.lock` requires >= 8.4.1)
- CI matrix and README now document PHP 8.4+ requirement

### Added

- Live demo on Render free tier with one-click Blueprint deploy
  (https://nobat-demo.onrender.com), auto-generated `APP_KEY` and a fresh
  seeded SQLite database on every boot
- Branding: app logo reads «نوبت» instead of the starter-kit placeholder

### Docs

- Real product screenshots (public home, booking wizard, login, dashboard)
- Mermaid architecture diagram, roadmap and contribution section in README
- `CONTRIBUTING.md`, `SECURITY.md`, GitHub issue templates and a pull
  request template

## [1.0.0] - 2026-11-05

### Added

#### Public booking
- Multi-business directory with cached listing
- Business profile page with services and staff
- 4-step booking wizard: service → staff → Jalali date & slots → customer details
- Slot availability API computing free slots from working hours, service
  duration, lead time, booking horizon and existing appointments
- Conflict-safe reservation (transaction + row locking)
- Booking confirmation page with ULID tracking code
- Rate limiting on slot lookup and booking submission

#### Dashboard & management
- Role-aware dashboard: today's schedule, upcoming appointments, weekly
  chart, KPI cards (today, pending, next week, monthly revenue)
- Appointments index with status / date / staff filters and status
  transitions with final-status freezing
- Services, staff and working-hours management for managers
- Super-admin businesses overview with activation toggle

#### Platform
- Roles & permissions via Spatie (super-admin, manager, staff, customer)
- Persian localization: RTL layout, Vazirmatn typeface, Jalali date picker
  (vendored TypeScript port of jalaali-js), fa validation messages
- Queued SMS notifications behind an SmsDriver abstraction (log driver
  included, real gateways plug-in-able)
- Docker stack: nginx, php-fpm, queue worker, MySQL 8, Redis 7
- GitHub Actions CI (Pint + Pest + asset build) and Dependabot
- 46 passing Pest tests covering slot engine, booking flow, authorization
  and status transitions

[1.16.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.16.0
[1.15.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.15.0
[1.14.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.14.0
[1.13.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.13.0
[1.12.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.12.0
[1.11.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.11.0
[1.10.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.10.0
[1.9.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.9.0
[1.8.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.8.0
[1.7.1]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.7.1
[1.7.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.7.0
[1.6.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.6.0
[1.5.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.5.0
[1.4.1]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.4.1
[1.4.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.4.0
[1.3.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.3.0
[1.2.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.2.0
[1.0.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.0.0
