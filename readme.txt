================================================================================
  THUOC360 — Huong dan cai dat tren may tinh ca nhan (Windows)
================================================================================

File zip nay chua ma nguon Laravel 10 + du lieu mau (database + anh logo).
Cach don gian nhat: dung Laragon (PHP + MySQL + Apache trong mot goi).

--------------------------------------------------------------------------------
1. YEU CAU
--------------------------------------------------------------------------------

  - Windows 10/11
  - Laragon Full: https://laragon.org/download/
    (chon ban co PHP 8.2 hoac 8.3)
  - Giai nen file zip vao thu muc, vi du:
      C:\laragon\www\thuoc360

  Neu ban da dung XAMPP/WAMP: van chay duoc, nhung Laragon it cau hinh hon.

--------------------------------------------------------------------------------
2. GIAI NEN
--------------------------------------------------------------------------------

  - Giai nen toan bo noi dung zip vao:
      C:\laragon\www\thuoc360
  - Trong thu muc phai thay: app, public, database, readme.txt, ...

--------------------------------------------------------------------------------
3. TAO DATABASE (MySQL)
--------------------------------------------------------------------------------

  a) Mo Laragon -> Start All
  b) Menu Laragon -> MySQL -> HeidiSQL (hoac phpMyAdmin)
  c) Tao database moi ten: thuoc360
  d) Import file:
      database\thuoc360_data.sql
     (chua stores, coupons, blog, tai khoan admin/member tu server)

--------------------------------------------------------------------------------
4. FILE CAU HINH .env
--------------------------------------------------------------------------------

  a) Copy file:
      .env.example  ->  .env
  b) Sua cac dong quan trong trong .env:

      APP_URL=http://thuoc360.test
      APP_DEBUG=true

      DB_DATABASE=thuoc360
      DB_USERNAME=root
      DB_PASSWORD=          (Laragon mac dinh de trong)

      SITE_DOMAIN=thuoc360.test
      SITE_URL=http://thuoc360.test

  c) Mo Terminal trong thu muc du an (Laragon: chuot phai thu muc -> Terminal):

      php artisan key:generate
      php artisan storage:link
      php artisan migrate --force

  Lenh migrate dam bao bang moi nhat; du lieu chinh lay tu file SQL import.

  QUAN TRONG — storage:link (buoc 4c):
  Anh store/blog luu trong storage\app\public\ nhung web chi doc qua public\storage\.
  Neu bo qua buoc nay, TAT CA anh se loi (404) du DB va file tren disk van dung.
  Xem chi tiet muc "9. LOI THUONG GAP" ben duoi.

--------------------------------------------------------------------------------
5. CHAY WEBSITE
--------------------------------------------------------------------------------

  Laragon tu tao domain: http://thuoc360.test
  (ten domain = ten thu muc trong C:\laragon\www\)

  Neu khong vao duoc:
  - Laragon -> Menu -> Apache -> Reload
  - Thu: http://localhost/thuoc360/public

  Dang nhap admin (tu database da import — DOI MAT KHAU sau khi cai):
  - URL: http://thuoc360.test/login
  - Email/mat khau: xem bang users trong HeidiSQL (cot email)

--------------------------------------------------------------------------------
6. LENH HUU ICH (chay trong thu muc du an)
--------------------------------------------------------------------------------

  composer install          Neu thieu thu muc vendor (lan dau)
  php artisan serve         Chay tam http://127.0.0.1:8000 (khong can Apache)
  php artisan view:clear    Xoa cache giao dien
  php artisan stores:fix-logos   Sua logo store neu loi

--------------------------------------------------------------------------------
6b. CHUONG TRINH GIOI THIEU (REFERRAL / AFFILIATE)
--------------------------------------------------------------------------------

  Sau khi migrate, moi user co ma gioi thieu (referral_code) va link rieng.

  Chuong trinh gioi thieu (Referral Program) mac dinh TAT.
  Bat lai bang cach them vao .env: AFFILIATE_PROGRAM_ENABLED=true
  (can php artisan config:clear sau khi doi .env)

  Member:
    Dashboard -> Referral Program (/dashboard/affiliate)
    - Copy link gioi thieu (hoac dung ?ref=MA trong URL)
    - Xem don hang duoc ghi nhan, so du hoa hong
    - Tao Yeu cau thanh toan hoa hong khi du so du toi thieu

  Admin:
    Admin -> Referral Program (/admin/affiliate/orders)
    - Log Order: ghi don hang tu khach duoc gioi thieu
    - Doi trang thai Paid sau khi khach da thanh toan -> tu dong cong hoa hong
    - Payout Requests (/admin/affiliate/payouts): xu ly yeu cau rut tien
    - Sau khi da chuyen tien cho member -> doi trang thai Paid
    - Neu tu choi -> chon Rejected (so du tra lai cho member)

  Cau hinh .env (tuy chon):
    AFFILIATE_COMMISSION_RATE=10
    AFFILIATE_MIN_PAYOUT=50
    AFFILIATE_CURRENCY=USD
    AFFILIATE_COOKIE_DAYS=30

  Khi member gui yeu cau rut tien, admin nhan email (neu MAIL cau hinh)
  va thong bao ghi vao laravel.log.

--------------------------------------------------------------------------------
7. KHONG CO TRONG ZIP (can cai them neu thieu)
--------------------------------------------------------------------------------

  - Thu muc vendor/  -> chay: composer install
  - File .env        -> copy tu .env.example (khong gui mat khau qua zip)

--------------------------------------------------------------------------------
8. DONG BO LEN SERVER SAU NAY
--------------------------------------------------------------------------------

  Sua code tren PC -> upload bang FTP/SFTP hoac Git len VPS
  Thu muc production thuong la: /var/www/thuoc360
  Sau khi upload: php artisan view:clear (va migrate neu co migration moi)
  Sau deploy lan dau hoac clone moi: php artisan storage:link (xem muc 9 neu anh loi)

--------------------------------------------------------------------------------
9. LOI THUONG GAP
--------------------------------------------------------------------------------

  "500 Server Error"
    -> Kiem tra .env, APP_KEY da generate, quyen ghi storage/ va bootstrap/cache/

  "SQLSTATE connection refused"
    -> Bat MySQL trong Laragon, kiem tra DB_DATABASE/DB_USERNAME

  Anh logo / featured blog khong hien (404) — LOI PHO BIEN SAU CAI DAT
  --------------------------------------------------------------------
  Trieu chung:
    - Dashboard stores: cot logo trong
    - Trang store cong khai (/stores/ten-store): logo loi
    - Trang blog (/blog): anh bai viet loi
    - URL dang http://thuoc360.test/storage/stores/... tra ve 404

  Nguyen nhan:
    - Anh KHONG bi luu sai vi tri. File nam dung tai:
        storage\app\public\stores\{userId}\logos\
        storage\app\public\posts\{userId}\
    - DB luu dung path tuong doi (vd: stores/1/logos/uuid.png)
    - Thieu symlink: public\storage phai tro toi storage\app\public
    - Neu public\storage chi la thu muc rong (co .gitignore) -> web khong doc duoc file

  Cach sua:
    cd C:\laragon\www\thuoc360    (hoac duong dan du an cua ban)

    Neu public\storage da ton tai ma van loi anh, xoa roi tao lai link:
      rmdir /s /q public\storage
      php artisan storage:link

    Hoac chi chay (lan cai dat moi):
      php artisan storage:link

  Kiem tra da OK:
    - Mo trinh duyet: http://thuoc360.test/storage/stores/1/logos/
      (neu thay danh sach thu muc/file -> symlink dung)
    - Reload dashboard stores, /stores/alsoasked, /blog -> anh hien binh thuong
    - KHONG can import lai store hay upload lai anh

  Lenh bo tro (neu logo van loi sau khi da storage:link):
    php artisan stores:fix-logos

  "Class not found" / thieu vendor
    -> composer install

--------------------------------------------------------------------------------
10. HO TRO KY THUAT
--------------------------------------------------------------------------------

  PHP: >= 8.1 (khuyen nghi 8.2+)
  Extensions: openssl, pdo_mysql, mbstring, tokenizer, xml, ctype, json, fileinfo, gd

  Chi tiet Laravel: https://laravel.com/docs/10.x

================================================================================
