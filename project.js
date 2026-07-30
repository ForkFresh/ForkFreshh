const burger = document.getElementById("burger");
const nav = document.getElementById("nav");
if (burger && nav) {
    burger.addEventListener("click", () => nav.classList.toggle("open"));
}

const searchBtn = document.getElementById("searchBtn");
const searchInput = document.getElementById("searchInput");
if (searchBtn && searchInput) {
    function go() {
        const q = searchInput.value.trim();
        if (q) window.location.href = "categories.php?q=" + encodeURIComponent(q);
    }
    searchBtn.addEventListener("click", go);
    searchInput.addEventListener("keypress", e => { if (e.key === "Enter") go(); });
}