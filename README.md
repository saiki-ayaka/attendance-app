# 勤怠管理システム

## 1. プロジェクト概要
* **どんなアプリか？**：要件定義に基づいた、ユーザーの勤怠と管理を行うアプリ
* **開発目的**：ユーザーの出勤・退勤・休憩時間を管理し、管理者への修正申請などを行うためのアプリ開発
* **開発期間**：2026年4月9日〜2026年5月30日

## 2. 開発環境・動作確認方法

採点者様の手元で動作確認いただくための手順です。

### 使用技術(実行環境)
- **開発言語/フレームワーク**:
  - PHP 8.1.34
  - Laravel 8.83.29
- **データベース**: MySQL 8.0.26
- **インフラ（コンテナ環境）**:
  - Docker / Docker Compose
- **Webサーバー**: nginx 1.21.1
- **管理ツール・その他**:
  - GitHub(バージョン管理)
  - MailHog(メールテスト用)
  - phpMyAdmin(DB管理)
  - VS Code(エディタ)

### 起動手順
#### 環境構築
1. リポジトリをクローンし、ディレクトリに移動します。
```bash
git clone https://github.com/saiki-ayaka/attendance-app.git
```
```bash
cd attendance-app
```
2. DockerDesktopアプリを立ち上げる
3. コンテナをビルド・起動します。
```bash
docker-compose up -d --build
```

**Laravel環境構築**
1. PHPコンテナ内に入ります。
```bash
docker-compose exec php bash
```
2. ライブラリをインストールします。
```bash
composer install
```
3. .env.example をコピーして .env を作成します。
   .env を作成したら、DB_PASSWORD の欄にご自身の環境に合わせて設定したパスワードを入力してください

```bash
cp .env.example .env
```

4. .env ファイルの「メール送信設定」欄が MAIL_HOST=mailhog 等になっていることを確認してください。（もし古い設定であれば、以下の内容に更新してください）

```text
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

5. アプリケーションキーの作成
``` bash
php artisan key:generate
```

6. ストレージのシンボリックリンク作成（画像表示に必要）
``` bash
php artisan storage:link
```

7. データベースの初期化とテストデータの投入
``` bash
php artisan migrate:fresh --seed
```

### アクセスURL
ローカルサーバー起動後、ブラウザで以下にアクセスしてください。
- 一般ユーザーログイン画面: http://localhost/login
- 一般ユーザー会員登録: http://localhost/register
- 管理者ログイン画面: http://localhost/admin/login

> ログイン後は、各権限に応じた勤怠打刻や申請管理画面へ自動的に遷移します。

### テスト用アカウント
動作確認の際は、以下の登録済みアカウントをご利用ください。
* **管理者アカウント**
    * メールアドレス: `admin@example.com`
    * パスワード: `password123`
* **一般ユーザーアカウント**
    * メールアドレス: `user@example.com`
    * パスワード: `password123`

> **補足:** 上記以外にも、DatabaseSeeder の実行により、ランダムなスタッフユーザーが **10名** 自動生成されます。

## 3. データベース設計
データの整合性を保つため、以下の設計に基づいています。
### ER図
![ER図](./docs/database-design.png)
### テーブル仕様書

#### usersテーブル (ユーザー管理)
| カラム名 | 型 | PK | UNIQUE | NOT NULL | 説明 |
| :--- | :--- | :---: | :---: | :---: | :--- |
| id | unsigned bigint | ○ | | ○ | ユーザーID |
| name | varchar(255) | | | ○ | ユーザー名 |
| email | varchar(255) | | ○ | ○ | メールアドレス |
| password | varchar(255) | | | ○ | パスワード |
| role | tinyint | | | ○ | 権限(1:一般, 2:管理者) |
| email_verified_at | timestamp | | | | メール認証日時 |
| remember_token | varchar(100) | | | | ログイン保持トークン |
| created_at | timestamp | | | ○ | 作成日時 |
| updated_at | timestamp | | | ○ | 更新日時 |

#### attendancesテーブル (勤怠管理)
| カラム名 | 型 | PK | NOT NULL | FK | 説明 |
| :--- | :--- | :---: | :---: | :---: | :--- |
| id | unsigned bigint | ○ | ○ | | 勤怠ID |
| user_id | unsigned bigint | | ○ | users(id) | ユーザーID |
| date | date | | ○ | | 勤務日 |
| status | tinyint | | ○ | | 承認状況(0:承認待ち, 1:承認済) |
| work_status | tinyint | | | | 勤務状態(0:勤外, 1:出勤, 2:休憩, 3:退勤) |
| start_time | datetime | | | | 出勤時刻 |
| end_time | datetime | | | | 退勤時刻 |
| remarks | varchar(255) | | | | 備考 |
| created_at | timestamp | | ○ | | 作成日時 |
| updated_at | timestamp | | ○ | | 更新日時 |

#### rest_timesテーブル (休憩管理)
| カラム名 | 型 | PK | NOT NULL | FK | 説明 |
| :--- | :--- | :---: | :---: | :---: | :--- |
| id | unsigned bigint | ○ | ○ | | 休憩ID |
| attendance_id | unsigned bigint | | ○ | attendances(id) | 勤怠ID |
| start_time | datetime | | ○ | | 休憩開始時刻 |
| end_time | datetime | | | | 休憩終了時刻 |
| created_at | timestamp | | ○ | | 作成日時 |
| updated_at | timestamp | | ○ | | 更新日時 |

#### stamp_correction_requestsテーブル (修正申請管理)
| カラム名 | 型 | PK | NOT NULL | FK | 説明 |
| :--- | :--- | :---: | :---: | :---: | :--- |
| id | unsigned bigint | ○ | ○ | | 申請ID |
| attendance_id | unsigned bigint | | ○ | attendances(id) | 勤怠ID |
| user_id | unsigned bigint | | ○ | users(id) | ユーザーID |
| date | date | | ○ | | 対象日 |
| status | tinyint | | ○ | | 承認状況(0:承認待ち, 1:承認済) |
| start_time | datetime | | | | 修正後出勤時刻 |
| end_time | datetime | | | | 修正後退勤時刻 |
| rest_data | json | | ○ | | 修正後休憩データ |
| remarks | varchar(255) | | | | 申請理由 |
| created_at | timestamp | | ○ | | 作成日時 |
| updated_at | timestamp | | | | 更新日時 |

## 4. 主要機能一覧

*   **ユーザー認証・認可**
    *   Laravel Fortify を活用した新規登録、ログイン（一般・管理者）、ログアウト機能
*   **勤怠打刻**
    *   日々の出勤、退勤、休憩開始、休憩終了の打刻機能
*   **勤怠一覧・詳細表示**
    *   月ごとの勤怠ログ一覧表示（日付、出勤・退勤時刻、休憩時間、合計勤務時間の集計）
    *   特定の日の詳細勤怠データの閲覧
*   **修正申請機能**
    *   勤怠の打刻漏れや誤りに対する修正申請の提出
    *   申請理由の入力および修正後データの送信
*   **管理者向け機能**
    *   全ユーザーの勤怠管理（一覧表示、詳細閲覧）
    *   スタッフ一覧の管理
    *   スタッフごとの過去の勤怠閲覧
    *   修正申請に対する承認・修正処理