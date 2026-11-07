(() => {
  const B = document.body, base = B.dataset.base || '', csrf = B.dataset.csrf, login = B.dataset.login === '1';
  const KEY = 'waka_guest_cart';
  const getCart = () => { try { const a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a : []; } catch { return []; } };
  const setCart = a => { try { localStorage.setItem(KEY, JSON.stringify(a)); } catch {} };
  const post = (url, data) => fetch(base + '/' + url, { method: 'POST', body: new URLSearchParams({ csrf, ...data }) }).then(r => r.json());

  // Giỏ khách (FR3.2): đếm số dòng; khi đăng nhập thì đồng bộ lên CSDL
  const badge = document.getElementById('so-gio');
  if (!login && badge) { const n = getCart().length; badge.textContent = n || ''; }
  if (login && getCart().length) {
    post('gio_hang.php', { action: 'dong_bo', items: JSON.stringify(getCart()) }).then(r => {
      const xong = r.processed || [];
      setCart(getCart().filter(k => !xong.includes(k)));
      if (badge && r.so_dong !== undefined) badge.textContent = r.so_dong || '';
      if (xong.length && /gio_hang\.php$/.test(location.pathname)) location.reload();
    }).catch(() => {});
  }
  // Nút thêm vào giỏ cho khách: data-gio="book:1" / "chapter:8"
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-gio-khach]');
    if (!b || login) return;
    e.preventDefault();
    const c = getCart(), k = b.dataset.gioKhach;
    if (!c.includes(k)) c.push(k);
    setCart(c); if (badge) badge.textContent = c.length;
    b.textContent = '✓ Đã thêm vào giỏ'; b.disabled = true;
  });

  // Gợi ý tìm kiếm (FR2.4): ≥ 2 ký tự, tối đa 5 sách + 3 tác giả
  const o = document.getElementById('o-tim'), box = document.getElementById('goi-y');
  if (o && box) {
    let t, seq = 0;
    const esc = s => s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    o.addEventListener('input', () => {
      clearTimeout(t);
      const q = o.value.trim();
      if (q.length < 2) { box.hidden = true; return; }
      t = setTimeout(async () => {
        const my = ++seq;
        const d = await fetch(base + '/api_goi_y.php?q=' + encodeURIComponent(q)).then(r => r.json()).catch(() => null);
        if (!d || my !== seq) return;
        let h = '';
        if (d.sach.length) h += '<h6>Sách</h6>' + d.sach.map(s => `<a href="${base}/chi_tiet.php?id=${s.id}"><img src="${esc(s.anh)}" alt=""><span>${esc(s.ten)}<br><small>${esc(s.tac_gia)}</small></span></a>`).join('');
        if (d.tac_gia.length) h += '<h6>Tác giả</h6>' + d.tac_gia.map(a => `<a href="${base}/danh_muc.php?tac_gia=${a.id}">✍ ${esc(a.ten)}</a>`).join('');
        box.innerHTML = h || '<h6>Không có gợi ý</h6>'; box.hidden = false;
      }, 200);
    });
    document.addEventListener('click', e => { if (!box.contains(e.target) && e.target !== o) box.hidden = true; });
  }
  document.querySelectorAll('[data-xacnhan]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.xacnhan)) e.preventDefault(); }));

  // Nút kính lúp bật/tắt ô tìm kiếm
  const ft = document.getElementById('form-tim'), nt = document.getElementById('nut-tim');
  if (ft && nt) nt.addEventListener('click', () => { ft.hidden = !ft.hidden; if (!ft.hidden) document.getElementById('o-tim').focus(); });
  // Nút chia sẻ: sao chép liên kết
  document.querySelectorAll('[data-chia-se]').forEach(b => b.addEventListener('click', async () => {
    const u = location.href.split('#')[0];
    try { if (navigator.share) await navigator.share({ title: document.title, url: u }); else { await navigator.clipboard.writeText(u); alert('Đã sao chép liên kết!'); } } catch {}
  }));
  // Băng chuyền trang chủ: chấm tròn + tự chuyển slide
  const bc = document.getElementById('bang-chuyen');
  if (bc) {
    const sl = [...bc.children], cham = document.getElementById('cham');
    const ve = i => cham && [...cham.children].forEach((c, k) => c.classList.toggle('on', k === i));
    const di = i => bc.scrollTo({ left: sl[i].offsetLeft - bc.offsetLeft, behavior: 'smooth' });
    if (cham) { cham.innerHTML = sl.map(() => '<i></i>').join(''); [...cham.children].forEach((c, i) => c.addEventListener('click', () => di(i))); ve(0); }
    bc.addEventListener('scroll', () => { const i = Math.round(bc.scrollLeft / (sl[0].offsetWidth + 18)); ve(Math.min(i, sl.length - 1)); }, { passive: true });
    let cur = 0;
    if (sl.length > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => { if (document.hidden) return; cur = (cur + 1) % sl.length; di(cur); }, 5000);
  }

  // Cửa sổ nổi khi rê chuột vào thẻ sách (chỉ thiết bị có chuột)
  if (matchMedia('(hover:hover) and (min-width:900px)').matches) {
    let pop = null, tm = 0, cur = null;
    const E = s => String(s).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    const tim = '<svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
    const sach = '<svg viewBox="0 0 24 24"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>';
    const dong = () => { clearTimeout(tm); if (pop) pop.remove(); pop = null; cur = null; };
    const mo = c => {
      dong(); cur = c;
      const d = c.dataset, href = c.getAttribute('href'), yt = d.yt === '1';
      pop = document.createElement('div'); pop.className = 'card-pop';
      pop.style.setProperty('--bia', `url("${d.anh.replace(/"/g, '%22')}")`);
      pop.innerHTML = `<a class="cp-bia" href="${E(href)}"><img src="${E(d.anh)}" alt=""></a><div class="cp-nd"><h4>${E(d.ten)}</h4>
        <div class="cp-nut"><a class="nut-doc" href="${E(href)}">${sach}${E(d.nut)}</a>
        ${login ? `<form method="post" action="${E(base)}/tu_sach.php"><input type="hidden" name="csrf" value="${E(csrf)}"><input type="hidden" name="ma_sach" value="${E(d.id)}"><input type="hidden" name="action" value="yeu_thich"><input type="hidden" name="dat" value="${yt ? 0 : 1}"><input type="hidden" name="quay_lai" value="${E(location.pathname.split('/').pop() + location.search)}"><button class="nut-tron nho${yt ? ' on' : ''}" aria-label="Yêu thích">${tim}</button></form>`
                 : `<a class="nut-tron nho" href="${E(base)}/dang_nhap.php" aria-label="Đăng nhập để yêu thích">${tim}</a>`}</div>
        <p>${E(d.mota || '')}</p><a class="chi-tiet" href="${E(href)}">Chi tiết</a></div>`;
      document.body.appendChild(pop);
      const r = c.getBoundingClientRect(), w = Math.min(720, innerWidth - 24);
      pop.style.width = w + 'px';
      const h = pop.offsetHeight;
      const left = Math.min(Math.max(r.left + r.width / 2 - w / 2, 12), innerWidth - w - 12);
      const top = Math.min(Math.max(r.top - 24, 8), Math.max(8, innerHeight - h - 8));
      pop.style.left = left + scrollX + 'px'; pop.style.top = top + scrollY + 'px';
    };
    document.addEventListener('mouseover', e => {
      if (e.target.closest('.card-pop')) { clearTimeout(tm); return; }
      const c = e.target.closest('.card-sach[data-id]');
      if (c) { if (c === cur) return; clearTimeout(tm); tm = setTimeout(() => mo(c), 280); }
      else dong();
    });
    window.addEventListener('scroll', () => { if (pop) dong(); }, { passive: true });
  }
})();
