<?php
session_start();
include 'db.php';

// ─── DATABASE (schema thực tế: tours không có Destination) ────────────────────
$categories = [];
$r_cats = mysqli_query($conn, "SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryID ASC LIMIT 6");
if ($r_cats) { while ($row = mysqli_fetch_assoc($r_cats)) $categories[] = $row; }

$total_tours = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tours");
if ($r && $row = mysqli_fetch_assoc($r)) $total_tours = (int)$row['c'];
// ─────────────────────────────────────────────────────────────────────────────

$cat_images = [
    'Du Lịch Biển'       => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=900',
    'Du Lịch Núi Rừng'   => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=900',
    'Du lịch Miền Tây'   => 'https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=900',
    'Du lịch Hành Hương' => 'https://images.unsplash.com/photo-1555217851-6141535bd771?q=80&w=900',
    'Du lịch Hải Đảo'    => 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?q=80&w=900',
    'Du Lịch Miền Trung' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?q=80&w=900',
];
$fallback = array_values($cat_images);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miền Tây Travel | Về chúng tôi</title>
    <meta name="description" content="Khám phá Miền Tây Travel – người bạn đồng hành trên những hành trình du lịch đáng nhớ tại Việt Nam.">

    <!-- ── Bootstrap & Icons ── -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- ── ĐỒNG BỘ FONT VỚI INDEX.PHP: Inter (giống style.css body) ── -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- ── Global CSS của project (chứa CSS variables, navbar, footer, v.v.) ── -->
    <link rel="stylesheet" href="style.css">

    <style>
    /* ═══ ABOUT PAGE LOCAL CSS ═══════════════════════════════════════════════
       Font: Inter (đồng bộ với style.css / index.php)
       Không override font-family của body — dùng global style.css
       ═════════════════════════════════════════════════════════════════════ */

    /* ── SCROLL REVEAL SYSTEM (2 chiều) ─────────────────────────────────── */
    /* Trạng thái ẩn ban đầu */
    .rv-up    { opacity:0; transform:translateY(60px);  transition:opacity .75s cubic-bezier(.22,1,.36,1), transform .75s cubic-bezier(.22,1,.36,1); }
    .rv-down  { opacity:0; transform:translateY(-60px); transition:opacity .75s cubic-bezier(.22,1,.36,1), transform .75s cubic-bezier(.22,1,.36,1); }
    .rv-left  { opacity:0; transform:translateX(-60px); transition:opacity .85s cubic-bezier(.22,1,.36,1), transform .85s cubic-bezier(.22,1,.36,1); }
    .rv-right { opacity:0; transform:translateX(60px);  transition:opacity .85s cubic-bezier(.22,1,.36,1), transform .85s cubic-bezier(.22,1,.36,1); }
    .rv-scale { opacity:0; transform:scale(.92);        transition:opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1); }
    .rv-img   { opacity:0; transform:scale(1.08); clip-path:inset(0 100% 0 0);
                transition:opacity .9s cubic-bezier(.22,1,.36,1), transform .9s cubic-bezier(.22,1,.36,1), clip-path .9s cubic-bezier(.22,1,.36,1); }
    /* Trạng thái hiện */
    .rv-up.is-v, .rv-down.is-v, .rv-left.is-v, .rv-right.is-v, .rv-scale.is-v
        { opacity:1; transform:none; }
    .rv-img.is-v { opacity:1; transform:scale(1); clip-path:inset(0 0 0 0); }

    /* Delay stagger */
    .d1 { transition-delay:.10s; } .d2 { transition-delay:.20s; }
    .d3 { transition-delay:.30s; } .d4 { transition-delay:.40s; }

    /* ── HERO ──────────────────────────────────────────────────────────── */
    .ab-hero {
        position:relative; min-height:90vh;
        display:flex; align-items:flex-end; overflow:hidden;
    }
    .ab-hero-bg {
        position:absolute; inset:0;
        /* Đã đổi sang ảnh phong cảnh con đường du lịch tuyệt đẹp */
        background:url('https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
        transform:scale(1.08);
        animation:heroBgZoom 1.4s cubic-bezier(.22,1,.36,1) forwards;
        will-change:transform;
    }
    @keyframes heroBgZoom { to { transform:scale(1); } }
    .ab-hero-grad {
        position:absolute; inset:0;
        background:linear-gradient(180deg,
            rgba(0,40,77,.18) 0%, rgba(0,40,77,.05) 35%,
            rgba(0,20,40,.72) 80%, rgba(0,10,25,.92) 100%);
        z-index:1;
    }
    .ab-hero-content { position:relative; z-index:2; padding:0 0 80px; width:100%; }

    /* Hero intro animations — chỉ chạy khi load, không restart khi scroll */
    .h-anim { opacity:0; transform:translateY(40px); }
    .h-a0 { animation:hFadeUp .8s cubic-bezier(.22,1,.36,1) .15s forwards; }
    .h-a1 { animation:hFadeUp .9s cubic-bezier(.22,1,.36,1) .3s  forwards; }
    .h-a2 { animation:hFadeUp .9s cubic-bezier(.22,1,.36,1) .48s forwards; }
    .h-a3 { animation:hFadeUp .9s cubic-bezier(.22,1,.36,1) .65s forwards; }
    @keyframes hFadeUp { to { opacity:1; transform:translateY(0); } }

    .hero-eyebrow {
        font-size:.72rem; font-weight:700; letter-spacing:4px;
        color:var(--orange); text-transform:uppercase;
    }
    /* Heading dùng Inter 900 — đồng bộ index */
    .hero-h1 {
        font-family:'Inter', 'Segoe UI', system-ui, sans-serif;
        font-size:clamp(2.4rem,7vw,5.5rem);
        font-weight:900; line-height:1.06; color:#fff;
        letter-spacing:-1px;
    }
    .hero-h1 .accent { color:var(--orange); }
    .hero-sub {
        font-size:clamp(.95rem,2vw,1.15rem);
        color:rgba(255,255,255,.82); line-height:1.75; max-width:520px;
    }

    /* Scroll indicator */
    .scroll-hint {
        position:absolute; bottom:26px; left:50%; transform:translateX(-50%);
        z-index:2; text-align:center; cursor:pointer;
        opacity:0; animation:hFadeUp 1s cubic-bezier(.22,1,.36,1) 1.1s forwards;
    }
    .scroll-hint span { display:block; font-size:.62rem; letter-spacing:3px; color:rgba(255,255,255,.5); text-transform:uppercase; margin-bottom:8px; }
    .scroll-arrow {
        display:block; width:24px; height:24px; margin:0 auto;
        border-right:2px solid rgba(255,255,255,.45); border-bottom:2px solid rgba(255,255,255,.45);
        transform:rotate(45deg);
        animation:arrowB 1.5s ease-in-out infinite;
    }
    @keyframes arrowB { 0%,100%{ transform:rotate(45deg) translateY(0); } 50%{ transform:rotate(45deg) translateY(6px); } }

    /* ── BUTTONS (đồng bộ style index) ──────────────────────────────── */
    .ab-btn {
        display:inline-flex; align-items:center; gap:8px;
        padding:13px 28px; border-radius:var(--radius-pill);
        font-family:'Inter', sans-serif; font-weight:700;
        font-size:.85rem; letter-spacing:.6px; text-transform:uppercase;
        text-decoration:none; border:none; cursor:pointer;
        transition:all .28s cubic-bezier(.22,1,.36,1);
    }
    .ab-btn-solid { background:var(--orange); color:#fff; box-shadow:0 8px 22px rgba(255,134,46,.3); }
    .ab-btn-solid:hover { background:var(--orange-hover); color:#fff; transform:translateY(-2px); box-shadow:0 14px 32px rgba(255,134,46,.4); }
    .ab-btn-outline { background:transparent; color:#fff; border:2px solid rgba(255,255,255,.65); }
    .ab-btn-outline:hover { background:#fff; color:var(--navy); transform:translateY(-2px); border-color:#fff; }
    .ab-btn-navy { background:var(--navy); color:#fff; box-shadow:0 8px 22px rgba(0,40,77,.2); }
    .ab-btn-navy:hover { background:var(--blue); color:#fff; transform:translateY(-2px); }

    /* ── SECTION COMMONS ────────────────────────────────────────────── */
    .ab-sec { padding:100px 0; }
    .ab-sec-sm { padding:70px 0; }
    .sec-label {
        font-family:'Inter', sans-serif;
        font-size:.68rem; font-weight:700; letter-spacing:4px;
        color:var(--orange); text-transform:uppercase; display:block; margin-bottom:10px;
    }
    /* Heading dùng Inter — đồng bộ index (không dùng serif) */
    .ab-h2 {
        font-family:'Inter', 'Segoe UI', system-ui, sans-serif;
        font-size:clamp(1.75rem,4vw,2.6rem);
        font-weight:800; line-height:1.15;
        color:var(--text-heading);
        letter-spacing:-.5px;
    }
    .ab-h2-white { color:#fff; }
    .sep { width:44px; height:3px; background:linear-gradient(90deg,var(--orange),var(--blue-bright)); border-radius:4px; margin:14px 0 22px; }

    /* ── STORY ──────────────────────────────────────────────────────── */
    .story-img-wrap { position:relative; border-radius:var(--radius-xl); overflow:hidden; box-shadow:var(--shadow-xl); }
    .story-img-main { width:100%; height:500px; object-fit:cover; transition:transform .7s cubic-bezier(.22,1,.36,1); }
    .story-img-wrap:hover .story-img-main { transform:scale(1.03); }
    .story-float {
        position:absolute; bottom:-24px; right:-20px;
        width:180px; height:145px; object-fit:cover;
        border-radius:var(--radius-lg); border:4px solid #fff;
        box-shadow:var(--shadow-lg);
        transition:transform .6s cubic-bezier(.22,1,.36,1);
    }
    .story-img-wrap:hover .story-float { transform:translateY(-8px); }
    .story-hl {
        font-style:italic; font-weight:700; font-size:1.05rem;
        color:var(--text-heading); position:relative; display:inline;
        background:linear-gradient(var(--orange),var(--orange)) no-repeat 0 100%;
        background-size:0% 2px;
        transition:background-size 1s cubic-bezier(.22,1,.36,1);
        padding-bottom:3px;
    }
    .story-hl.is-v { background-size:100% 2px; }

    /* ── JOURNEY GALLERY (horizontal continuous marquee carousel) ──── */
    .j-sec {
        background: var(--navy);
        padding: 90px 0 75px;
        overflow: hidden;
        position: relative;
    }
    .j-header-wrap {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 28px;
    }
    .j-btn-all {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        border-radius: var(--radius-pill);
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: 0.3px;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(.22,1,.36,1);
        white-space: nowrap;
        backdrop-filter: blur(8px);
    }
    .j-btn-all i {
        color: var(--orange);
        transition: transform 0.25s ease;
    }
    .j-btn-all:hover {
        background: rgba(255, 255, 255, 0.18);
        border-color: rgba(255, 134, 46, 0.6);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    }
    .j-btn-all:hover i {
        transform: translateX(4px);
    }

    /* Carousel container & marquee strip */
    .j-carousel-wrapper {
        position: relative;
        width: 100%;
        overflow: hidden;
        padding: 16px 0 20px;
        mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
    }
    .j-marquee {
        display: flex;
        width: max-content;
        gap: 20px;
        will-change: transform;
        animation: marqueeScroll 32s linear infinite;
    }
    .j-carousel-wrapper:hover .j-marquee {
        animation-play-state: paused;
    }
    .j-group {
        display: flex;
        gap: 20px;
        flex-shrink: 0;
    }

    @keyframes marqueeScroll {
        from {
            transform: translate3d(0, 0, 0);
        }
        to {
            transform: translate3d(-50%, 0, 0);
        }
    }

    .j-card {
        position: relative;
        border-radius: var(--radius-lg);
        overflow: hidden;
        flex: 0 0 280px;
        width: 280px;
        height: 400px;
        cursor: pointer;
        user-select: none;
        transition: transform 0.4s cubic-bezier(.22,1,.36,1), box-shadow 0.35s ease;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .j-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
    }
    .j-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(.22,1,.36,1);
    }
    .j-card:hover img {
        transform: scale(1.08);
    }
    .j-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,20,50,.92) 0%, rgba(0,20,50,.5) 45%, transparent 70%);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 24px 20px;
        transition: background 0.35s ease;
    }
    .j-card:hover .j-overlay {
        background: linear-gradient(to top, rgba(0,20,50,.98) 0%, rgba(0,20,50,.7) 50%, rgba(0,0,0,.15) 80%);
    }
    .j-name {
        font-weight: 800;
        font-size: 1.2rem;
        color: #fff;
        margin-bottom: 6px;
        text-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }
    .j-desc {
        font-size: 0.82rem;
        color: rgba(255,255,255,.78);
        line-height: 1.45;
        max-height: 42px;
        overflow: hidden;
        transition: color 0.3s ease;
    }
    .j-card:hover .j-desc {
        color: rgba(255,255,255,.95);
    }
    .j-link {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--orange);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        transition: color 0.25s ease, transform 0.25s ease;
    }
    .j-card:hover .j-link {
        color: var(--orange-hover);
        transform: translateX(4px);
    }

    @media (max-width: 768px) {
        .j-card {
            flex: 0 0 240px;
            width: 240px;
            height: 350px;
        }
        .j-carousel-wrapper {
            mask-image: none;
            -webkit-mask-image: none;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .j-carousel-wrapper::-webkit-scrollbar {
            display: none;
        }
        .j-header-wrap {
            align-items: flex-start;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .j-marquee {
            animation: none !important;
            overflow-x: auto;
        }
    }

    /* ── STATS ──────────────────────────────────────────────────────── */
    .stats-sec { background:#fff; padding:90px 0; border-top:1px solid var(--border); }
    .stat-num { font-size:clamp(2.8rem,5.5vw,4.5rem); font-weight:900; color:var(--navy); line-height:1; }
    .stat-num .sfx { color:var(--orange); }
    .stat-sep { width:36px; height:3px; background:linear-gradient(90deg,var(--orange),var(--blue-bright)); border-radius:3px; margin:12px 0 10px; }
    .stat-lbl { font-size:.78rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--text-secondary); }
    .stat-col { border-right:1px solid var(--border); padding:16px 40px; }
    .stat-col:last-child { border-right:none; }
    @media(max-width:767px){ .stat-col { border-right:none; border-bottom:1px solid var(--border); padding:20px; } }

    /* ── BIG TYPE ────────────────────────────────────────────────────── */
    .bt-sec { background:var(--navy); padding:110px 0; overflow:hidden; }
    .bt-word {
        display:block; font-weight:900; line-height:1;
        font-size:clamp(52px,12vw,130px); letter-spacing:-2px;
        /* Trạng thái ẩn — sẽ được IntersectionObserver điều khiển */
    }
    .bt-w1 { color:#fff; }
    .bt-w2 { color:var(--blue-bright); }
    .bt-w3 { color:var(--orange); }

    /* ── WHY CHOOSE ─────────────────────────────────────────────────── */
    .why-sec { padding:100px 0; background:var(--bg-main); }
    .why-card {
        background:var(--white); border-radius:var(--radius-xl); padding:36px 28px;
        height:100%; border:1.5px solid var(--border); box-shadow:var(--shadow-sm);
        transition:all .35s cubic-bezier(.22,1,.36,1); position:relative; overflow:hidden;
    }
    .why-card::after {
        content:''; position:absolute; top:0; left:0; right:0; height:4px;
        background:linear-gradient(90deg,var(--blue),var(--blue-bright));
        transform:scaleX(0); transform-origin:left; transition:transform .35s cubic-bezier(.22,1,.36,1);
    }
    .why-card:hover { transform:translateY(-10px); box-shadow:var(--shadow-xl); border-color:rgba(0,85,143,.15); }
    .why-card:hover::after { transform:scaleX(1); }
    .why-num { font-size:4rem; font-weight:900; color:rgba(0,85,143,.05); position:absolute; top:14px; right:18px; line-height:1; }
    .why-icon {
        width:60px; height:60px; border-radius:var(--radius-md); margin-bottom:20px;
        display:flex; align-items:center; justify-content:center;
        background:var(--blue-light); font-size:1.6rem; color:var(--blue);
        transition:all .35s cubic-bezier(.22,1,.36,1);
    }
    .why-card:hover .why-icon {
        background:linear-gradient(135deg,var(--orange),#FF6B00); color:#fff;
        transform:scale(1.1) rotate(-5deg); box-shadow:0 10px 24px rgba(255,134,46,.3);
    }
    .why-card h4 { font-size:1rem; font-weight:800; color:var(--text-heading); margin-bottom:8px; }
    .why-card p  { font-size:.88rem; color:var(--text-secondary); line-height:1.75; margin:0; }

    /* ── TIMELINE ────────────────────────────────────────────────────── */
    .tl-sec { padding:100px 0; background:var(--white); }
    .tl-wrap { position:relative; max-width:740px; margin:0 auto; }
    .tl-bg  { position:absolute; left:50%; top:0; bottom:0; width:3px; background:var(--border); margin-left:-1.5px; border-radius:3px; }
    .tl-fill { position:absolute; left:50%; top:0; width:3px; height:0; background:linear-gradient(to bottom,var(--blue-bright),var(--orange)); margin-left:-1.5px; border-radius:3px; transition:height 1.4s ease-out; }
    .tl-item { display:flex; padding:22px 0; position:relative; opacity:0; transform:translateY(28px); transition:opacity .7s cubic-bezier(.22,1,.36,1),transform .7s cubic-bezier(.22,1,.36,1); }
    .tl-item.is-v { opacity:1; transform:translateY(0); }
    .tl-item:nth-child(odd)  { flex-direction:row; }
    .tl-item:nth-child(even) { flex-direction:row-reverse; }
    .tl-body { width:calc(50% - 28px); background:var(--bg-main); border-radius:var(--radius-lg); padding:20px 22px; }
    .tl-item:nth-child(odd)  .tl-body { margin-right:28px; border-right:4px solid var(--blue-bright); }
    .tl-item:nth-child(even) .tl-body { margin-left:28px;  border-left:4px solid var(--orange); }
    .tl-dot { position:absolute; left:50%; top:30px; width:20px; height:20px; border-radius:50%; background:var(--orange); border:3px solid #fff; box-shadow:0 0 0 4px rgba(255,134,46,.2); transform:translateX(-50%); z-index:2; transition:transform .4s cubic-bezier(.22,1,.36,1); }
    .tl-item.is-v .tl-dot { transform:translateX(-50%) scale(1.15); }
    .tl-body h4 { font-size:.95rem; font-weight:800; color:var(--text-heading); margin-bottom:6px; }
    .tl-body p  { font-size:.85rem; color:var(--text-secondary); line-height:1.75; margin:0; }
    @media(max-width:767px){
        .tl-bg,.tl-fill { left:20px; }
        .tl-item,.tl-item:nth-child(even) { flex-direction:column; padding-left:46px; }
        .tl-body { width:100%; margin:0!important; border-right:none!important; border-left:none!important; border-top:4px solid var(--blue-bright); }
        .tl-item:nth-child(even) .tl-body { border-top-color:var(--orange); }
        .tl-dot { left:20px; }
    }

    /* ── EXPERIENCE ──────────────────────────────────────────────────── */
    .exp-sec { padding:100px 0; background:var(--bg-main); overflow:hidden; }
    .exp-main-img { width:100%; height:540px; object-fit:cover; border-radius:var(--radius-xl); box-shadow:var(--shadow-xl); }
    .exp-item {
        display:flex; align-items:flex-start; gap:16px; padding:18px 20px;
        border-radius:var(--radius-lg); border:1.5px solid transparent;
        transition:all .3s cubic-bezier(.22,1,.36,1);
    }
    .exp-item:hover { background:var(--white); border-color:var(--border); box-shadow:var(--shadow-md); transform:translateX(5px); }
    .exp-icon {
        width:54px; height:54px; flex-shrink:0; border-radius:var(--radius-md);
        display:flex; align-items:center; justify-content:center;
        font-size:1.4rem; color:#fff;
        background:linear-gradient(135deg,var(--blue),var(--blue-bright));
        box-shadow:0 8px 18px rgba(0,85,143,.22);
        transition:transform .3s cubic-bezier(.22,1,.36,1);
    }
    .exp-item:hover .exp-icon { transform:scale(1.1) rotate(-5deg); }
    .exp-item h5 { font-size:.95rem; font-weight:800; color:var(--text-heading); margin-bottom:4px; }
    .exp-item p  { font-size:.86rem; color:var(--text-secondary); line-height:1.7; margin:0; }


    /* ── CTA ─────────────────────────────────────────────────────────── */
    .cta-fin { position:relative; min-height:540px; display:flex; align-items:center; justify-content:center; overflow:hidden; text-align:center; }
    .cta-bg  { position:absolute; inset:0; background:url('https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?q=80&w=2070') center/cover; animation:slowZ 10s ease-in-out infinite alternate; }
    @keyframes slowZ { from{ transform:scale(1); } to{ transform:scale(1.06); } }
    .cta-overlay { position:absolute; inset:0; background:linear-gradient(135deg,rgba(0,20,50,.9) 0%,rgba(0,85,143,.55) 60%,rgba(255,134,46,.14) 100%); }
    .cta-body { position:relative; z-index:2; padding:60px 20px; }
    .cta-h { font-size:clamp(1.8rem,4.5vw,3.5rem); font-weight:900; color:#fff; line-height:1.1; margin-bottom:14px; letter-spacing:-.5px; }
    .cta-sub { font-size:1rem; color:rgba(255,255,255,.82); margin-bottom:32px; }

    /* ── REDUCED MOTION ──────────────────────────────────────────────── */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration:.01ms !important;
            animation-iteration-count:1 !important;
            transition-duration:.01ms !important;
            scroll-behavior:auto !important;
        }
        .rv-up,.rv-down,.rv-left,.rv-right,.rv-scale,.rv-img,
        .h-anim,.tl-item,.bt-word {
            opacity:1!important; transform:none!important; clip-path:none!important;
        }
    }

    /* AI chat */
    @keyframes pulse { 0%{ box-shadow:0 0 0 0 rgba(255,134,46,.6); } 70%{ box-shadow:0 0 0 14px rgba(255,134,46,0); } 100%{ box-shadow:0 0 0 0 rgba(255,134,46,0); } }
    @keyframes scaleUp { from{ transform:scale(.85); opacity:0; } to{ transform:scale(1); opacity:1; } }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<!-- ══════════════════════ 1. HERO ══════════════════════════════════ -->
<section class="ab-hero" id="ab-hero">
    <div class="ab-hero-bg"></div>
    <div class="ab-hero-grad"></div>
    <div class="container ab-hero-content">
        <p class="hero-eyebrow h-anim h-a0 mb-3">VỀ CHÚNG TÔI - MIỀN TÂY TRAVEL</p>
        <h1 class="hero-h1 h-anim h-a1 mb-4">
            CÙNG BẠN<br>
            <span class="accent">VIẾT NÊN</span><br>
            NHỮNG HÀNH TRÌNH
        </h1>
        <p class="hero-sub h-anim h-a2 mb-5">
            Chúng tôi không chỉ đưa bạn đến những vùng đất mới, mà còn cùng bạn<br>
            kiến tạo nên những kỷ niệm khó quên trên mọi nẻo đường Việt Nam.
        </p>
        <div class="d-flex gap-3 flex-wrap h-anim h-a3">
            <a href="index.php#tour-section" class="ab-btn ab-btn-solid"><i class="bi bi-compass"></i> Khám phá hành trình</a>
            <a href="#ab-story" class="ab-btn ab-btn-outline"><i class="bi bi-info-circle"></i> Câu chuyện của chúng tôi</a>
        </div>
    </div>
    <div class="scroll-hint" onclick="document.getElementById('ab-story').scrollIntoView({behavior:'smooth'})">
        <span>Scroll to explore</span>
        <span class="scroll-arrow"></span>
    </div>
</section>

<!-- ══════════════════════ 2. STORY ═════════════════════════════════ -->
<section class="ab-sec" id="ab-story" style="background:var(--bg-main);">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 rv-left" data-rv>
                <div class="story-img-wrap">
                    <img class="story-img-main" loading="lazy"
                         src="https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=900&auto=format&fit=crop"
                         alt="Hành trình Miền Tây Travel">
                    <img class="story-float" loading="lazy"
                         src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=400"
                         alt="Biển Việt Nam" id="floatImg">
                </div>
            </div>
            <div class="col-lg-6 rv-right" data-rv>
                <span class="sec-label">OUR STORY</span>
                <h2 class="ab-h2 mt-1 mb-3">KHÔNG CHỈ LÀ<br>MỘT CHUYẾN ĐI.</h2>
                <div class="sep"></div>
                <p style="color:var(--text-body); line-height:1.85; margin-bottom:18px;">
                    Đó là một hành trình để khám phá, kết nối và lưu giữ những khoảnh khắc đáng nhớ. Miền Tây Travel được xây dựng với mong muốn mang đến những hành trình thuận tiện, chất lượng và giàu trải nghiệm cho du khách trên mọi nẻo đường Việt Nam.
                </p>
                <p style="color:var(--text-body); line-height:1.85; margin-bottom:28px;">
                    Chúng tôi tin rằng mỗi chuyến đi không chỉ là việc đến một địa điểm, mà là cơ hội để khám phá bản thân và kết nối với văn hóa bản địa.
                </p>
                <span class="story-hl" id="storyHL" data-rv-hl>
                    "Đi xa để trở về gần hơn với những điều mình yêu."
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════ 3. JOURNEY GALLERY ═══════════════════════ -->
<section class="j-sec" id="jSec">
    <div class="container">
        <div class="j-header-wrap">
            <div class="rv-up" data-rv>
                <span class="sec-label" style="color:rgba(255,166,0,.85);">ĐIỂM ĐẾN</span>
                <h2 class="ab-h2 ab-h2-white mt-1 mb-2">MỖI ĐIỂM ĐẾN<br>MỘT CÂU CHUYỆN</h2>
                <p class="mb-0" style="color:rgba(255,255,255,.62); font-size:.9rem; max-width:480px;">
                    Từ sông nước Đồng bằng đến đỉnh núi mù sương, mỗi nơi là một câu chuyện chờ bạn viết tiếp.
                </p>
            </div>
            <div class="rv-up d1" data-rv>
                <a href="index.php#tour-section" class="j-btn-all" title="Xem danh sách tất cả các điểm đến và tour">
                    <span>Xem tất cả điểm đến</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <?php
    $j_imgs = [
        'Du Lịch Biển'       => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=600',
        'Du Lịch Núi Rừng'   => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=600',
        'Du lịch Miền Tây'   => 'https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=600',
        'Du lịch Hành Hương' => 'https://images.unsplash.com/photo-1555217851-6141535bd771?q=80&w=600',
        'Du lịch Hải Đảo'    => 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?q=80&w=600',
        'Du Lịch Miền Trung' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?q=80&w=600',
    ];
    $j_descs = [
        'Du Lịch Biển'       => 'Bãi biển xanh ngọc, cát trắng mịn.',
        'Du Lịch Núi Rừng'   => 'Không khí trong lành, đỉnh núi hùng vĩ.',
        'Du lịch Miền Tây'   => 'Sông nước, chợ nổi, hương vị miền Nam.',
        'Du lịch Hành Hương' => 'Bình yên tâm hồn, nét văn hóa tâm linh.',
        'Du lịch Hải Đảo'    => 'Đảo ngọc nguyên sơ, trải nghiệm biển khơi.',
        'Du Lịch Miền Trung' => 'Di sản văn hóa, phố cổ, ẩm thực đặc trưng.',
    ];
    ?>

    <?php if (!empty($categories)): ?>
    <div class="j-carousel-wrapper" id="jCarouselWrapper" aria-label="Danh sách điểm đến">
        <div class="j-marquee" id="jMarquee">
            <!-- Nhóm thẻ 1 -->
            <div class="j-group">
                <?php foreach ($categories as $idx => $cat): 
                    $cname = $cat['CategoryName']; 
                    $cid   = $cat['CategoryID'];
                    $img   = $j_imgs[$cname] ?? $fallback[$idx % count($fallback)];
                    $desc  = $j_descs[$cname] ?? 'Khám phá điểm đến tuyệt vời.';
                ?>
                <div class="j-card" onclick="window.location.href='index.php?category=<?php echo $cid; ?>#tour-section'">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($cname); ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=600';">
                    <div class="j-overlay">
                        <div class="j-name"><?php echo htmlspecialchars($cname); ?></div>
                        <div class="j-desc"><?php echo htmlspecialchars($desc); ?></div>
                        <a class="j-link" href="index.php?category=<?php echo $cid; ?>#tour-section">Xem tour <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Nhóm thẻ 2 (bản sao nhân bản để vòng lặp chạy liền mạch không khoảng trống) -->
            <div class="j-group" aria-hidden="true">
                <?php foreach ($categories as $idx => $cat): 
                    $cname = $cat['CategoryName']; 
                    $cid   = $cat['CategoryID'];
                    $img   = $j_imgs[$cname] ?? $fallback[$idx % count($fallback)];
                    $desc  = $j_descs[$cname] ?? 'Khám phá điểm đến tuyệt vời.';
                ?>
                <div class="j-card" onclick="window.location.href='index.php?category=<?php echo $cid; ?>#tour-section'">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($cname); ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1528127269322-539801943592?q=80&w=600';">
                    <div class="j-overlay">
                        <div class="j-name"><?php echo htmlspecialchars($cname); ?></div>
                        <div class="j-desc"><?php echo htmlspecialchars($desc); ?></div>
                        <a class="j-link" href="index.php?category=<?php echo $cid; ?>#tour-section">Xem tour <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="color:rgba(255,255,255,.5); padding:60px 0; text-align:center; width:100%;">
        <i class="bi bi-map" style="font-size:2.5rem;"></i>
        <p class="mt-3">Chưa có danh mục trong hệ thống.</p>
    </div>
    <?php endif; ?>
</section>

<!-- ══════════════════════ 4. STATS ══════════════════════════════════ -->
<section class="stats-sec" id="statsSec">
    <div class="container">
        <div class="row g-0 text-center">
            <div class="col-6 col-md-3 stat-col rv-scale" data-rv>
                <div class="stat-num"><span class="counter" data-target="<?php echo max($total_tours,50); ?>">0</span><span class="sfx">+</span></div>
                <div class="stat-sep mx-auto"></div>
                <div class="stat-lbl">Hành trình</div>
            </div>
            <div class="col-6 col-md-3 stat-col rv-scale d1" data-rv>
                <div class="stat-num"><span class="counter" data-target="50">0</span><span class="sfx">+</span></div>
                <div class="stat-sep mx-auto"></div>
                <div class="stat-lbl">Điểm đến</div>
            </div>
            <div class="col-6 col-md-3 stat-col rv-scale d2" data-rv>
                <div class="stat-num" style="color:var(--blue-bright);">24/7</div>
                <div class="stat-sep mx-auto"></div>
                <div class="stat-lbl">Hỗ trợ</div>
            </div>
            <div class="col-6 col-md-3 stat-col rv-scale d3" data-rv>
                <div class="stat-num"><span class="counter" data-target="100">0</span><span class="sfx">%</span></div>
                <div class="stat-sep mx-auto"></div>
                <div class="stat-lbl">Tận tâm</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════ 5. BIG TYPOGRAPHY ═════════════════════════ -->
<section class="bt-sec" id="btSec">
    <div class="container">
        <span class="bt-word bt-w1 rv-left" data-rv id="btw1">ĐI.</span>
        <span class="bt-word bt-w2 rv-left d1" data-rv id="btw2">KHÁM PHÁ.</span>
        <span class="bt-word bt-w3 rv-left d2" data-rv id="btw3">CẢM NHẬN.</span>
    </div>
</section>

<!-- ══════════════════════ 6. WHY CHOOSE US ══════════════════════════ -->
<section class="why-sec" id="whySec">
    <div class="container">
        <div class="text-center rv-up mb-5" data-rv>
            <span class="sec-label">GIÁ TRỊ CỐT LÕI</span>
            <h2 class="ab-h2 mt-2">VÌ SAO ĐỒNG HÀNH<br>CÙNG MIỀN TÂY TRAVEL?</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3 rv-up d1" data-rv>
                <div class="why-card"><div class="why-num">01</div><div class="why-icon"><i class="bi bi-map"></i></div><h4>Hành trình chọn lọc</h4><p>Được thiết kế hướng đến trải nghiệm thực tế và thuận tiện nhất cho mọi du khách.</p></div>
            </div>
            <div class="col-md-6 col-lg-3 rv-up d2" data-rv>
                <div class="why-card"><div class="why-num">02</div><div class="why-icon"><i class="bi bi-cash-coin"></i></div><h4>Giá cả minh bạch</h4><p>Chi phí trình bày rõ ràng, không phụ phí ẩn để khách hàng dễ dàng lựa chọn.</p></div>
            </div>
            <div class="col-md-6 col-lg-3 rv-up d3" data-rv>
                <div class="why-card"><div class="why-num">03</div><div class="why-icon"><i class="bi bi-headset"></i></div><h4>Hỗ trợ tận tâm</h4><p>Đồng hành cùng khách trước, trong và sau chuyến đi — 24/7.</p></div>
            </div>
            <div class="col-md-6 col-lg-3 rv-up d4" data-rv>
                <div class="why-card"><div class="why-num">04</div><div class="why-icon"><i class="bi bi-stars"></i></div><h4>Trải nghiệm đáng nhớ</h4><p>Không chỉ là một chuyến đi, mà là những khoảnh khắc lưu giữ cảm xúc mãi mãi.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════ 7. TIMELINE ════════════════════════════════ -->
<section class="tl-sec" id="tlSec">
    <div class="container">
        <div class="text-center rv-up mb-5" data-rv>
            <span class="sec-label">HÀNH TRÌNH THƯƠNG HIỆU</span>
            <h2 class="ab-h2 mt-2">HÀNH TRÌNH KIẾN TẠO</h2>
        </div>
        <div class="tl-wrap" id="tlWrap">
            <div class="tl-bg"></div>
            <div class="tl-fill" id="tlFill"></div>
            <div class="tl-item" data-tl>
                <div class="tl-body"><h4>01. KHỞI ĐẦU</h4><p>Bắt đầu từ những chuyến đi nhỏ tại vùng Đồng bằng sông Cửu Long, mang đậm chất tình người miền Tây.</p></div>
                <div class="tl-dot"></div>
            </div>
            <div class="tl-item" data-tl>
                <div class="tl-dot"></div>
                <div class="tl-body"><h4>02. MỞ RỘNG HÀNH TRÌNH</h4><p>Vươn ra khỏi miền Tây, khai phá các tuyến điểm trên mọi miền Tổ quốc từ Nam ra Bắc.</p></div>
            </div>
            <div class="tl-item" data-tl>
                <div class="tl-body"><h4>03. ỨNG DỤNG CÔNG NGHỆ</h4><p>Ra mắt hệ thống đặt tour trực tuyến giúp khách hàng dễ dàng tìm kiếm và thanh toán nhanh chóng.</p></div>
                <div class="tl-dot"></div>
            </div>
            <div class="tl-item" data-tl>
                <div class="tl-dot"></div>
                <div class="tl-body"><h4>04. ĐỒNG HÀNH CÙNG DU KHÁCH</h4><p>Tiếp tục sứ mệnh mang lại những chuyến đi trọn vẹn, lan tỏa văn hóa và kết nối cộng đồng.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════ 8. EXPERIENCE ══════════════════════════════ -->
<section class="exp-sec" id="expSec">
    <div class="container">
        <div class="row align-items-start g-5">
            <div class="col-lg-5">
                <div class="rv-left" data-rv>
                    <span class="sec-label">TRẢI NGHIỆM</span>
                    <h2 class="ab-h2 mt-2 mb-3">ĐI ĐỂ<br>CẢM NHẬN</h2>
                    <div class="sep"></div>
                    <p style="color:var(--text-secondary); line-height:1.85; margin-bottom:28px;">
                        Không chỉ là điểm đến — mà là những khoảnh khắc sống, cảm nhận và ký ức được dệt nên từ văn hóa, thiên nhiên và ẩm thực Việt Nam.
                    </p>
                </div>
                <div class="exp-item rv-up d1" data-rv>
                    <div class="exp-icon"><i class="bi bi-tree"></i></div>
                    <div><h5>Khám phá thiên nhiên</h5><p>Hòa mình vào phong cảnh tuyệt đẹp, hít thở không khí trong lành giữa thiên nhiên hùng vĩ.</p></div>
                </div>
                <div class="exp-item rv-up d2 mt-3" data-rv>
                    <div class="exp-icon" style="background:linear-gradient(135deg,var(--orange),#FF6B00)"><i class="bi bi-cup-hot"></i></div>
                    <div><h5>Thưởng thức ẩm thực</h5><p>Mỗi món ăn là một câu chuyện văn hóa độc đáo được lưu truyền qua từng thế hệ.</p></div>
                </div>
                <div class="exp-item rv-up d3 mt-3" data-rv>
                    <div class="exp-icon" style="background:linear-gradient(135deg,#10B981,#059669)"><i class="bi bi-people"></i></div>
                    <div><h5>Kết nối văn hóa</h5><p>Giao lưu cùng người dân bản địa, cảm nhận lòng hiếu khách chân phương của người Việt.</p></div>
                </div>
            </div>
            <div class="col-lg-7 rv-right" data-rv>
                <img class="exp-main-img" loading="lazy"
                     src="https://images.unsplash.com/photo-1583417319070-4a69db38a482?q=80&w=1200&auto=format&fit=crop"
                     alt="Trải nghiệm du lịch Việt Nam">
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════ 10. CTA FINAL ══════════════════════════════ -->
<section class="cta-fin" id="ctaFin">
    <div class="cta-bg"></div>
    <div class="cta-overlay"></div>
    <div class="cta-body rv-scale" data-rv>
        <p style="font-size:.68rem; letter-spacing:4px; color:rgba(255,166,0,.9); text-transform:uppercase; margin-bottom:14px;">Bắt đầu ngay hôm nay</p>
        <h2 class="cta-h">CHUYẾN ĐI TIẾP THEO<br>CỦA BẠN ĐANG CHỜ</h2>
        <p class="cta-sub">Hãy để Miền Tây Travel đồng hành cùng bạn trên hành trình tiếp theo.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="index.php#tour-section" class="ab-btn ab-btn-solid"><i class="bi bi-compass"></i> Khám phá tour</a>
            <a href="#ab-hero" class="ab-btn ab-btn-outline"><i class="bi bi-telephone"></i> Liên hệ tư vấn</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>


<!-- ══════════════════════ SCRIPTS ══════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ───────────────────────────────────────────────────────────────
   SCROLL REVEAL 2 CHIỀU
   Không unobserve → animation chạy lại khi scroll lên/xuống
──────────────────────────────────────────────────────────────── */
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const rvEls = document.querySelectorAll('[data-rv]');
const rvObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('is-v');
        } else {
            e.target.classList.remove('is-v');   // ← KHÔNG unobserve → reset khi rời viewport
        }
    });
}, { threshold: 0.13, rootMargin: '0px 0px -40px 0px' });

rvEls.forEach(el => rvObs.observe(el));

/* ── Story Highlight Underline (2 chiều) ── */
const hlEl = document.getElementById('storyHL');
if (hlEl) {
    const hlObs = new IntersectionObserver(([e]) => {
        e.isIntersecting ? hlEl.classList.add('is-v') : hlEl.classList.remove('is-v');
    }, { threshold: 0.5 });
    hlObs.observe(hlEl);
}

/* ── Timeline Items (2 chiều) ── */
const tlItems = document.querySelectorAll('[data-tl]');
tlItems.forEach(el => {
    const to = new IntersectionObserver(([e]) => {
        e.isIntersecting ? el.classList.add('is-v') : el.classList.remove('is-v');
    }, { threshold: 0.25 });
    to.observe(el);
});

/* ── Timeline Line Fill (theo scroll progress) ── */
const tlWrap = document.getElementById('tlWrap');
const tlFill = document.getElementById('tlFill');
if (tlWrap && tlFill && !prefersReduced) {
    const tlLineObs = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) {
            tlFill.style.height = '100%';
        } else {
            tlFill.style.height = '0';   // reset khi ra ngoài
        }
    }, { threshold: 0.05 });
    tlLineObs.observe(tlWrap);
}

/* ── Counter Animation (chạy lại khi scroll lên/xuống) ── */
if (!prefersReduced) {
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseInt(el.dataset.target, 10);
        let animating = false;

        const cObs = new IntersectionObserver(([e]) => {
            if (e.isIntersecting && !animating) {
                animating = true;
                el.textContent = '0';
                const dur = 1500, fps = 60, frames = Math.round(dur / (1000/fps));
                let frame = 0;
                const tick = () => {
                    frame++;
                    el.textContent = Math.round(target * Math.min(frame/frames, 1));
                    if (frame < frames) requestAnimationFrame(tick);
                    else { el.textContent = target; animating = false; }
                };
                requestAnimationFrame(tick);
            } else if (!e.isIntersecting) {
                animating = false;
                el.textContent = '0';   // reset để chạy lại lần sau
            }
        }, { threshold: 0.4 });
        cObs.observe(el);
    });
}

/* ── Floating Image Parallax (nhẹ, passive) ── */
if (!prefersReduced) {
    const floatImg = document.getElementById('floatImg');
    let rafId;
    window.addEventListener('scroll', () => {
        if (!floatImg) return;
        cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            const rect = floatImg.closest('section').getBoundingClientRect();
            const p = Math.max(0, Math.min(1, -rect.top / window.innerHeight));
            floatImg.style.transform = `translateY(${p * -18}px)`;
        });
    }, { passive: true });
}

/* ── Journey Marquee Carousel Touch Swipe Support ── */
(function() {
    const wrap = document.getElementById('jCarouselWrapper');
    const marquee = document.getElementById('jMarquee');
    if (!wrap || !marquee) return;

    let touchStartX = 0;
    let touchMoveX = 0;
    let isSwiping = false;

    wrap.addEventListener('touchstart', function(e) {
        if (e.touches.length === 1) {
            touchStartX = e.touches[0].clientX;
            isSwiping = true;
            marquee.style.animationPlayState = 'paused';
        }
    }, { passive: true });

    wrap.addEventListener('touchmove', function(e) {
        if (!isSwiping || e.touches.length !== 1) return;
        touchMoveX = e.touches[0].clientX;
        const diff = touchStartX - touchMoveX;
        wrap.scrollLeft += diff * 0.15;
    }, { passive: true });

    wrap.addEventListener('touchend', function() {
        isSwiping = false;
        marquee.style.animationPlayState = 'running';
    }, { passive: true });
})();


</script>
</body>
</html>