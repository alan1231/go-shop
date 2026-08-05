    </main>
<script>
(function () {
    const main = document.querySelector('.main-content');
    if (!main) return;

    function runScripts(root) {
        var scripts = Array.prototype.slice.call(root.querySelectorAll('script'));
        return scripts.reduce(function (p, s) {
            return p.then(function () {
                return new Promise(function (resolve) {
                    if (s.src) {
                        var ns = document.createElement('script');
                        ns.src = s.src;
                        ns.onload = resolve;
                        ns.onerror = resolve;
                        document.body.appendChild(ns);
                    } else {
                        var ni = document.createElement('script');
                        ni.textContent = s.textContent;
                        document.body.appendChild(ni);
                        resolve();
                    }
                });
            });
        }, Promise.resolve());
    }

    function load(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.querySelector('.main-content');
                if (!next) throw new Error('no main');
                main.innerHTML = next.innerHTML;
                document.title = doc.title;
                return runScripts(main).then(function () {
                    document.querySelectorAll('#sidebarNav a').forEach(function (a) {
                        a.classList.toggle('active', a.getAttribute('href') === url);
                    });
                    window.scrollTo(0, 0);
                });
            });
    }

    document.getElementById('sidebarNav').addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        e.preventDefault();
        load(a.getAttribute('href'))
            .then(function () { history.pushState(null, '', a.getAttribute('href')); })
            .catch(function () { window.location.href = a.getAttribute('href'); });
    });

    window.addEventListener('popstate', function () {
        load(location.href).catch(function () { location.reload(); });
    });
})();
</script>
</body>
</html>