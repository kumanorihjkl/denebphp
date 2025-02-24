# PHP BaaS - Model Synchronization System

このシステムは、フロントエンド側で定義されたデータモデルを基に、PHP側で自動的にDBスキーマを更新できる仕組みを提供するBaaSシステムです。

## 機能概要

- フロントエンドで定義したデータモデルをJSON形式で送信
- 現在のDBスキーマとの差分を自動計算
- マイグレーションの自動生成と適用
- トランザクション処理によるロールバック機能
- バージョン管理システム
- SQLiteとMySQLの両方をサポート

## システム要件

- PHP 8.1以上
- Composer
- データベース（SQLite3またはMySQL 8.0）
- Docker（MySQLを使用する場合）

## インストール

1. リポジトリのクローン:
```bash
git clone https://github.com/yourusername/php-baas.git
cd php-baas
```

2. 依存パッケージのインストール:
```bash
composer install
```

3. 環境設定:
```bash
# .envファイルの作成
cp .env.example .env
```

4. データベースの準備:

### SQLiteを使用する場合:
```bash
# SQLiteデータベースファイルの作成
mkdir -p database
touch database/baas.sqlite
chmod 777 database/baas.sqlite

# .envの設定
DB_DRIVER=sqlite
```

### MySQLを使用する場合:
```bash
# Dockerでの環境構築
docker-compose up -d

# .envの設定
DB_DRIVER=mysql
DB_HOST=localhost
DB_DATABASE=baas
DB_USERNAME=baas_user
DB_PASSWORD=baas_password
```

## 開発サーバーの起動

```bash
php -S localhost:8080 -t public/
```

## データベースの選択

システムは2つのデータベースドライバーをサポートしています：

1. **SQLite** (デフォルト)
   - 軽量で設定が簡単
   - ファイルベースのデータベース
   - 小規模なプロジェクトに最適

2. **MySQL**
   - スケーラブルな本番環境向け
   - 複数の同時接続をサポート
   - Dockerで簡単に環境構築可能

データベースの選択は `.env` ファイルの `DB_DRIVER` で設定できます：

```env
# SQLiteを使用する場合
DB_DRIVER=sqlite

# MySQLを使用する場合
DB_DRIVER=mysql
DB_HOST=localhost
DB_DATABASE=baas
DB_USERNAME=baas_user
DB_PASSWORD=baas_password
```

## Docker環境

MySQLを使用する場合、Dockerを使用して簡単に環境を構築できます：

```bash
# MySQLコンテナの起動
docker-compose up -d

# コンテナの状態確認
docker-compose ps

# ログの確認
docker-compose logs

# コンテナの停止
docker-compose down

# データを保持したままコンテナを停止
docker-compose down --volumes
```

Docker環境の設定は `docker-compose.yml` で管理されており、必要に応じてカスタマイズできます。

## API仕様

### 1. バージョン確認

**エンドポイント**: `GET /api/model-sync/version`

**レスポンス例**:
```json
{
  "currentVersion": "1.0.0",
  "lastMigrationDate": "2025-02-24T08:22:24+00:00"
}
```

### 2. モデル定義の差分取得

**エンドポイント**: `POST /api/model-sync/diff`

**リクエスト例**:
```json
{
  "version": "1.0.0",
  "models": [
    {
      "name": "User",
      "fields": [
        {
          "name": "id",
          "type": "integer",
          "primary": true,
          "autoIncrement": true
        },
        {
          "name": "name",
          "type": "string",
          "length": 255,
          "nullable": false
        },
        {
          "name": "email",
          "type": "string",
          "length": 255,
          "unique": true
        }
      ]
    }
  ]
}
```

**レスポンス例**:
```json
{
  "status": "diff",
  "currentVersion": "0.9.0",
  "newVersion": "1.0.0",
  "changes": [
    {
      "action": "add_table",
      "table": "User",
      "fields": [
        {
          "name": "id",
          "type": "integer",
          "length": null,
          "nullable": false,
          "default": null,
          "autoIncrement": true
        },
        {
          "name": "name",
          "type": "string",
          "length": 255,
          "nullable": false,
          "default": null,
          "autoIncrement": false
        },
        {
          "name": "email",
          "type": "string",
          "length": 255,
          "nullable": false,
          "default": null,
          "autoIncrement": false
        }
      ]
    }
  ]
}
```

### 3. マイグレーションの適用

**エンドポイント**: `POST /api/model-sync/apply`

**リクエスト例**:
```json
{
  "version": "1.0.0",
  "confirm": true,
  "changes": [
    {
      "action": "add_table",
      "table": "User",
      "fields": [
        {
          "name": "id",
          "type": "integer",
          "primary": true,
          "autoIncrement": true
        },
        {
          "name": "name",
          "type": "string",
          "length": 255,
          "nullable": false
        },
        {
          "name": "email",
          "type": "string",
          "length": 255,
          "unique": true
        }
      ]
    }
  ]
}
```

**レスポンス例**:
```json
{
  "status": "success",
  "message": "Migration applied successfully.",
  "currentVersion": "1.0.0"
}
```

## 動作確認方法

1. バージョン確認:
```bash
curl http://localhost:8080/api/model-sync/version
```

2. モデル定義の差分計算:
```bash
curl -X POST http://localhost:8080/api/model-sync/diff \
  -H "Content-Type: application/json" \
  -d '{
    "version": "1.0.0",
    "models": [
      {
        "name": "User",
        "fields": [
          {"name": "id", "type": "integer", "primary": true, "autoIncrement": true},
          {"name": "name", "type": "string", "length": 255, "nullable": false},
          {"name": "email", "type": "string", "length": 255, "unique": true}
        ]
      }
    ]
  }'
```

3. マイグレーション適用:
```bash
curl -X POST http://localhost:8080/api/model-sync/apply \
  -H "Content-Type: application/json" \
  -d '{
    "version": "1.0.0",
    "confirm": true,
    "changes": [
      {
        "action": "add_table",
        "table": "User",
        "fields": [
          {"name": "id", "type": "integer", "primary": true, "autoIncrement": true},
          {"name": "name", "type": "string", "length": 255, "nullable": false},
          {"name": "email", "type": "string", "length": 255, "unique": true}
        ]
      }
    ]
  }'
```

## プロジェクト構造

```
php-baas/
├── config/
│   └── database.php      # データベース設定
├── database/
│   └── baas.sqlite       # SQLiteデータベース（SQLite使用時）
├── docker/              # Docker関連ファイル
│   └── mysql/           # MySQL設定
├── public/
│   ├── .htaccess        # Apacheリライトルール
│   └── index.php        # アプリケーションエントリーポイント
├── src/
│   ├── Controllers/     # コントローラー
│   ├── Database/        # データベース関連クラス
│   ├── Models/          # モデルクラス
│   ├── Services/        # ビジネスロジック
│   └── Migrations/      # マイグレーション
├── tests/               # テストファイル
├── .env                 # 環境変数
├── .env.example         # 環境変数のサンプル
├── composer.json        # Composer設定
├── docker-compose.yml   # Docker構成
└── README.md           # プロジェクトドキュメント
```

## エラーハンドリング

システムは以下のような場合にエラーレスポンスを返します：

1. 不正なモデル定義フォーマット:
```json
{
  "status": "error",
  "error": "Invalid model definition format."
}
```

2. マイグレーション失敗:
```json
{
  "status": "error",
  "error": "Migration failed due to schema conflict.",
  "details": "Field 'email' in table 'User' already exists with conflicting properties."
}
```

## 開発者向け情報

### サポートされているデータ型

- `string`: 文字列（デフォルト長: 255）
- `integer`: 整数
- `text`: 長文テキスト
- `datetime`: 日時
- `boolean`: 真偽値
- `float`: 浮動小数点数

### フィールドオプション

- `primary`: 主キー
- `autoIncrement`: 自動増分
- `nullable`: NULL許容
- `unique`: 一意性制約
- `length`: 文字列長
- `default`: デフォルト値
- `foreignKey`: 外部キー参照

## トラブルシューティング

### MySQL接続エラー
- Docker環境が起動していることを確認
- `.env`の接続情報が正しいことを確認
- `docker-compose ps`でコンテナの状態を確認

### SQLiteエラー
- `database`ディレクトリの権限を確認
- SQLiteファイルの書き込み権限を確認
- `DB_DRIVER=sqlite`が設定されていることを確認

## ライセンス

MIT License
