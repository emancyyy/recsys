const PER_PAGE = 5;
let recs = [];
let page = 0;

let currentUser = localStorage.getItem("recsys_user")
    ? parseInt(localStorage.getItem("recsys_user"))
    : null;

function qs(id) { return document.getElementById(id); }

function setUserUI() {
    qs("currentUser").textContent =
        currentUser ? `User: ${currentUser}` : "Неавторизован";
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]
    );
}

async function fetchJSON(url, opts) {
    const r = await fetch(url, opts);
    return r.json();
}

async function initDB() {
    await fetchJSON("/api/init.php");
}

async function loadRecommendations() {

    qs("loading").classList.remove("hidden");

    const uid = currentUser ?? 0;

    const data = await fetchJSON(`/api/recommend.php?user_id=${uid}&limit=30`);
    recs = data.recommendations || [];

    page = 0;
    renderPage();

    qs("loading").classList.add("hidden");
}

function renderPage() {
    const start = page * PER_PAGE;
    const items = recs.slice(start, start + PER_PAGE);

    const feed = qs("feedList");
    feed.innerHTML = "";

    if (items.length === 0) {
        feed.innerHTML = `<div class="empty muted">Нет постов</div>`;
    }

    items.forEach(item => {
        const card = document.createElement("div");
        card.className = "post glass";

        card.innerHTML = `
            <div class="post-head">
                <div class="post-title">${escapeHtml(item.title)}</div>
                <button class="like-btn">♡</button>
            </div>
            <div class="post-body">${escapeHtml(item.body || "").slice(0, 280)}</div>
        `;

        const btn = card.querySelector(".like-btn");

        btn.addEventListener("click", () => toggleLike(item.id, btn));

        updateLikeState(item.id, btn);

        feed.appendChild(card);
    });

    qs("pageInfo").textContent =
        `${page + 1} / ${Math.max(1, Math.ceil(recs.length / PER_PAGE))}`;
}

async function updateLikeState(contentId, btn) {
    if (!currentUser) {
        btn.textContent = "♡";
        return;
    }

    try {
        const state = await fetch("/api/toggle_like.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                user_id: currentUser,
                content_id: contentId,
                _check: true
            })
        });

        const json = await state.json();
        btn.textContent = json.liked ? "♥" : "♡";

    } catch (_) {
        btn.textContent = "♡";
    }
}

async function toggleLike(contentId, btn) {
    if (!currentUser) {
        openLoginModal();
        return;
    }

    const r = await fetch("/api/toggle_like.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            user_id: currentUser,
            content_id: contentId
        })
    });

    const json = await r.json();

    btn.textContent = json.liked ? "♥" : "♡";

    await loadRecommendations();
}


// ===== PAGING =====
qs("prevPage").onclick = () => {
    if (page > 0) {
        page--;
        renderPage();
    }
};

qs("nextPage").onclick = () => {
    if ((page + 1) * PER_PAGE < recs.length) {
        page++;
        renderPage();
    }
};

qs("refreshRec").onclick = loadRecommendations;


// ==== LOGIN MODAL ====

const modal = document.getElementById("modal");
const loginBtn = document.getElementById("btnLogin");
const loginSave = document.getElementById("loginSave");
const loginCancel = document.getElementById("loginCancel");
const loginInput = document.getElementById("loginInput");

// ГЛАВНАЯ ФУНКЦИЯ — СКРЫТИЕ МОДАЛА (ГАРАНТИРОВАННО)
function closeLoginModal() {
    modal.style.display = "none";     // ← ЖЁСТКОЕ скрытие
    modal.classList.add("hidden");
}

// Показ модала
function openLoginModal() {
    modal.style.display = "flex";      // ← ЖЁСТКИЙ показ
    modal.classList.remove("hidden");
    loginInput.focus();
}

loginBtn.onclick = openLoginModal;

loginCancel.onclick = () => {
    closeLoginModal();
};

// Клик по фону модального окна = закрытие
modal.addEventListener("click", (e) => {
    if (e.target === modal) {
        closeLoginModal();
    }
});

// Кнопка Войти внутри модала
loginSave.onclick = async () => {

    const id = parseInt(loginInput.value);
    if (!id) {
        alert("Введите корректный ID");
        return;
    }

    currentUser = id;
    localStorage.setItem("recsys_user", id);

    setUserUI();
    closeLoginModal();   // <----- ВОТ ЭТА СТРОКА ДОЛЖНА РАБОТАТЬ 100%

    await loadRecommendations();
};




// ===== INIT =====
setUserUI();
initDB().then(loadRecommendations);
