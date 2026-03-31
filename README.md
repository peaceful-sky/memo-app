# 📝 Memo App

다크모드 메모 웹 애플리케이션

<img width="2551" height="1276" alt="image" src="https://github.com/user-attachments/assets/b49a59fa-0b33-4f94-b1e8-4b2000a3b26f" />

프리뷰는 [서버포럼 게시글](https://svrforum.com/software/3066660)에서 확인할수 있습니다

## Changelog
26.03.11
 - 마크다운 미리보기 개선
 - 미리보기로 기본값변경

## 기술 스택

| 레이어 | 기술 |
|--------|------|
| OS | Alpine Linux 3.19 (미러: mirror.krfoss.org/alpine) |
| Web Server | Nginx |
| Runtime | PHP 8.4-FPM |
| Database | SQLite (WAL 모드) |
| 컨테이너 | Docker + Docker Compose |

## 시작하기

### 요구사항
- Docker Engine 20.10+
- Docker Compose v2+

### 실행

```bash
# 빌드 및 실행
docker compose up -d --build

# 브라우저에서 접속
open http://localhost:8080
```

### 종료

```bash
docker compose down
```

### 데이터 초기화 (DB 삭제 포함)

```bash
docker compose down -v
```

## 기능

- **회원가입 / 로그인** — bcrypt 해시 비밀번호, 세션 기반 인증
- **메모 CRUD** — 생성, 읽기, 수정, 삭제
- **메모 고정** — 중요한 메모를 상단 고정
- **컬러 태그** — 6가지 컬러로 메모 분류
- **실시간 검색** — 제목/내용 즉시 검색
- **영구 저장** — Docker Volume으로 컨테이너 재시작 후에도 데이터 유지

## 단축키

| 키 | 동작 |
|----|------|
| `Ctrl/Cmd + K` | 검색창 포커스 |
| `Ctrl/Cmd + Enter` | 메모 저장 (편집 중) |
| `Esc` | 모달 닫기 |

## 파일 구조

```
memo-app/
├── Dockerfile
├── docker-compose.yml
├── docker-entrypoint.sh
├── nginx/
│   ├── nginx.conf
│   └── default.conf
├── php/
│   └── php-fpm.conf
└── src/
    ├── index.html
    ├── db/
    │   ├── init.php
    │   └── db.php
    └── api/
        ├── auth.php       # 인증 API
        ├── memos.php      # 메모 CRUD API
        ├── helpers.php    # 공통 헬퍼
        └── health.php     # 헬스체크
```

## 데이터 영속성

SQLite DB 파일은 `/var/db/memo.db`에 저장되며, Docker Named Volume(`memo-db`)으로 마운트되어 컨테이너를 재시작하거나 재빌드해도 데이터가 유지됩니다.
