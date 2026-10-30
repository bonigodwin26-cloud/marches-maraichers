import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './passkeys.js';

const config = window.marketplaceConfig;
const route = config.routes;

function productImage(name = '') {
    const product = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    if (product.includes('tomat')) return '/images/products/tomate.jpg';
    if (product.includes('oignon')) return '/images/products/oignon.jpg';
    if (product.includes('piment')) return '/images/products/piment.jpg';
    if (product.includes('gombo')) return '/images/products/gombo.jpg';
    if (product.includes('aubergine')) return '/images/products/aubergine-locale.jpg';
    if (product.includes('feuille') || product.includes('amarante') || product.includes('epinard')) return '/images/products/legume-feuille.jpg';
    return '/images/products/legume-feuille.jpg';
}

function Icon({ name, className = '' }) {
    return <span aria-hidden="true" className={`ui-icon ${className}`.trim()} style={{ '--icon-url': `url('/icons/${name}.svg')` }} />;
}

function DemoDataNotice() {
    return config.props.demoMode ? <aside className="demo-data-notice"><Icon name="info" /><span><strong>Mode démonstration</strong> · Les relevés affichés sont simulés et ne représentent pas des prix collectés sur le terrain.</span></aside> : null;
}

function Topbar() {
    const [openPanel, setOpenPanel] = useState(null);
    const [searchOpen, setSearchOpen] = useState(false);
    const [theme, setTheme] = useState(() => localStorage.getItem('market-theme') === 'dark' ? 'dark' : 'light');
    const user = config.user;

    useEffect(() => {
        document.body.dataset.theme = theme;
        localStorage.setItem('market-theme', theme);
    }, [theme]);

    useEffect(() => {
        const close = (event) => {
            if (event.type === 'keydown' && event.key === 'Escape') setOpenPanel(null);
            if (event.type === 'click' && !event.target.closest('.topbar-popover-anchor')) setOpenPanel(null);
        };
        document.addEventListener('click', close);
        document.addEventListener('keydown', close);
        return () => {
            document.removeEventListener('click', close);
            document.removeEventListener('keydown', close);
        };
    }, []);

    const toggle = (name) => setOpenPanel((current) => current === name ? null : name);
    return <header className="app-topbar">
        <form className={`global-search${searchOpen ? ' is-open' : ''}`} method="GET" action={route.prices}><button className="search-toggle" type="button" aria-label={searchOpen ? 'Fermer la recherche' : 'Ouvrir la recherche'} aria-expanded={searchOpen} onClick={() => setSearchOpen((opened) => !opened)}><Icon name="search" /></button><input aria-label="Rechercher un produit ou un marché" type="search" name="q" defaultValue={config.query.q || ''} placeholder="Rechercher un produit, un marché..." autoFocus={searchOpen} /></form>
        <div className="topbar-actions">
            <div className="topbar-popover-anchor">
                <button className="topbar-notice" id="notificationsButton" type="button" aria-label="Ouvrir les notifications" aria-expanded={openPanel === 'notifications'} onClick={() => toggle('notifications')}><Icon name="bell" /></button>
                {openPanel === 'notifications' && <section className="topbar-popover notification-panel"><h2>Notifications</h2><p>{user ? 'Suivez les décisions et les mises à jour liées à vos demandes.' : 'Connectez-vous pour suivre les mises à jour de vos demandes de mise en relation.'}</p><a href={user?.isAdmin ? route.admin : user ? route.requests : route.login}>{user?.isAdmin ? 'Ouvrir l’espace équipe' : user ? 'Consulter mes demandes' : 'Se connecter'} <Icon name="arrow-right" /></a></section>}
            </div>
            <button className="theme-chip" id="themeToggle" type="button" aria-label={theme === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre'} aria-pressed={theme === 'dark'} onClick={() => setTheme((current) => current === 'dark' ? 'light' : 'dark')}><span className="theme-glyph"><Icon name={theme === 'dark' ? 'moon' : 'sun'} /></span><span className="theme-switch" aria-hidden="true"><i /></span><span className="sr-only">Changer de thème</span></button>
            <div className="topbar-popover-anchor account-anchor">
                <button className="account-menu account-trigger" id="accountButton" type="button" aria-expanded={openPanel === 'account'} onClick={() => toggle('account')}><span className="account-avatar">{user ? user.name.slice(0, 1).toLocaleUpperCase('fr-FR') : <Icon name="user-round" />}</span><span>Mon compte</span><Icon name="chevron-down" className="account-chevron" /></button>
                {openPanel === 'account' && <div className="topbar-popover account-panel">{user ? <><div className="popover-user">Connecté en tant que<strong>{user.name}</strong></div><a href={route.requests}><Icon name="mail" /> Mes demandes</a>{user.isAdmin && <a href={route.admin}><Icon name="chart-no-axes-column-increasing" /> Espace équipe</a>}<form method="POST" action={route.logout}><input type="hidden" name="_token" value={config.csrf} /><button type="submit"><Icon name="log-out" /> Se déconnecter</button></form></> : <><a href={route.login}><Icon name="arrow-right" /> Se connecter</a><a href={route.register}><Icon name="user-round" /> Créer mon compte</a></>}</div>}
            </div>
        </div>
    </header>;
}

const navItems = [
    ['home', 'Accueil', 'house', route.home],
    ['prices', 'Prix du jour', 'chart-no-axes-column-increasing', route.prices],
    ['markets', 'Marchés', 'store', route.markets],
    ['products', 'Produits', 'leaf', route.products],
    ['about', 'À propos', 'info', route.about],
];

function Sidebar() {
    return <aside className="app-sidebar"><a className="sidebar-brand" href={route.home}><span className="brand-leaf"><Icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Information de prix</small></span></a><nav className="sidebar-nav" aria-label="Navigation principale">
        {navItems.map(([key, label, icon, href]) => <a key={key} className={config.page === key ? 'active' : ''} aria-current={config.page === key ? 'page' : undefined} href={href}><Icon name={icon} />{label}</a>)}
        {config.user && !config.user.isAdmin && <a className={config.page === 'requests' ? 'active' : ''} aria-current={config.page === 'requests' ? 'page' : undefined} href={route.requests}><Icon name="mail" />Mes demandes</a>}
        {config.user?.isAdmin && <a className={config.page === 'admin' ? 'active' : ''} aria-current={config.page === 'admin' ? 'page' : undefined} href={route.admin}><Icon name="chart-no-axes-column-increasing" />Espace équipe</a>}
    </nav><div className="sidebar-motto"><span><Icon name="sprout" /></span><p>Des prix justes<br />pour une agriculture<br />plus forte !</p><i /></div></aside>;
}

function Footer() {
    return <>
        <div className="app-footer"><span><Icon name="leaf" className="footer-icon" />Produits locaux<small>Frais et de saison</small></span><span><Icon name="map-pin" className="footer-icon" />Marchés du Bénin<small>De votre région</small></span><span><Icon name="users-round" className="footer-icon" />Producteurs & commerçants<small>Ensemble pour une meilleure agriculture</small></span><span><Icon name="heart-handshake" className="footer-icon" />Une info utile<small>Pour tous les acteurs du marché</small></span></div>
        <footer className="site-footer">
            <div className="site-footer-main">
                <div className="site-footer-brand"><a href={route.home}><span className="brand-leaf"><Icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Information de prix</small></span></a><p>Des repères fiables pour mieux comprendre les prix et les marchés maraîchers du Bénin.</p></div>
                <div className="site-footer-column"><h2>Explorer</h2><a href={route.prices}>Prix du jour</a><a href={route.markets}>Marchés</a><a href={route.products}>Produits</a></div>
                <div className="site-footer-column"><h2>La plateforme</h2><a href={route.about}>À propos</a><a href={route.register}>Créer un compte acheteur</a><a href={route.login}>Se connecter</a></div>
                <div className="site-footer-contact"><span className="site-footer-kicker">Une question ?</span><h2>Restons en contact</h2><p>Pour demander une mise en relation, choisissez un prix publié et envoyez votre demande à l’équipe.</p><a className="site-footer-contact-link" href={route.prices}>Consulter les prix <Icon name="arrow-right" /></a></div>
            </div>
            <div className="site-footer-bottom"><span>© {new Date().getFullYear()} Marchés maraîchers du Bénin</span><span>Des informations pour des échanges plus éclairés.</span></div>
        </footer>
    </>;
}

function ObservationCard({ observation }) {
    return <article className="price-card"><div className="product-photo-frame price-product-photo"><img src={productImage(observation.product?.name)} alt={observation.product?.name || 'Produit maraîcher'} loading="lazy" /><span className="status-pill">Publié</span></div><h3>{observation.product?.name}</h3><p className="location">{observation.market?.name}{observation.market?.commune ? ` · ${observation.market.commune}` : ''}</p><div className="price-value">{new Intl.NumberFormat('fr-FR').format(observation.price_xof)} <small>FCFA</small></div><p className="unit">par {observation.unit_snapshot}{observation.quality ? ` · ${observation.quality}` : ''}</p>{observation.available_quantity && <p className="unit">Offre déclarée : {new Intl.NumberFormat('fr-FR').format(observation.available_quantity)} {observation.unit_snapshot}</p>}<div className="card-footer"><span><Icon name="calendar-days" /> Collecté le {observation.observed_at}</span><a href={`${route.priceDetailPrefix}/${observation.id}`}>Détails <Icon name="arrow-right" /></a></div></article>;
}

function EmptyState({ title, text, icon = 'search' }) {
    return <div className="empty-state directory-empty"><span className="empty-icon"><Icon name={icon} /></span><h3>{title}</h3><p>{text}</p></div>;
}

const homeHeroSlides = [
    {
        src: '/images/home-hero/01-market-seattle.jpg',
        title: 'Le maraîchage béninois,',
        highlight: 'mieux informé.',
        description: 'Des repères fiables pour rapprocher producteurs, commerçants et acheteurs.',
    },
    {
        src: '/images/home-hero/02-market-colourful.jpg',
        title: 'Des marchés vivants,',
        highlight: 'des prix plus justes.',
        description: 'Suivez les prix des produits maraîchers observés sur les marchés du Bénin.',
    },
    {
        src: '/images/home-hero/03-delhi-produce.jpg',
        title: 'Des produits locaux,',
        highlight: 'de saison.',
        description: 'Repérez les cultures suivies et comparez les prix selon les marchés.',
    },
    {
        src: '/images/home-hero/04-vegetable-field.jpg',
        title: 'Du champ au marché,',
        highlight: 'chaque récolte compte.',
        description: 'Les collectes terrain rapprochent producteurs, commerçants et acheteurs.',
    },
    {
        src: '/images/home-hero/05-farmers-baskets.jpg',
        title: 'Une agriculture forte,',
        highlight: 'mieux connectée.',
        description: 'Des informations utiles pour prendre de meilleures décisions au quotidien.',
    },
];

function HomePage({ data }) {
    const [activeSlide, setActiveSlide] = useState(0);

    useEffect(() => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined;
        const interval = window.setInterval(() => {
            setActiveSlide((current) => (current + 1) % homeHeroSlides.length);
        }, 6500);
        return () => window.clearInterval(interval);
    }, []);

    const slide = homeHeroSlides[activeSlide];

    return <><section className="market-hero home-hero"><div className="hero-carousel" aria-hidden="true">{homeHeroSlides.map((item, index) => <div key={item.src} className={`hero-background-slide${activeSlide === index ? ' is-active' : ''}`} style={{ backgroundImage: `url("${item.src}")` }} />)}</div><div className="hero-copy" key={activeSlide}><div className="hero-tag"><Icon name="map-pin" /> Plateforme d’information · Bénin</div><h1>{slide.title}<br /><em>{slide.highlight}</em></h1><p>{slide.description}</p><div className="home-actions"><a className="button button-primary" href={route.prices}><Icon name="chart-no-axes-column-increasing" /> Consulter les prix</a><a className="button button-light" href={route.markets}>Explorer les marchés</a></div></div><div className="hero-stat"><span className="stat-orb"><Icon name="chart-no-axes-combined" /></span><span>Relevés publiés</span><strong>{data.publishedCount}</strong><small>observations disponibles</small></div><div className="hero-carousel-indicators" role="group" aria-label="Images de présentation">{homeHeroSlides.map((item, index) => <button key={item.src} type="button" className={activeSlide === index ? 'is-active' : ''} aria-label={`Afficher l’image ${index + 1}: ${item.title} ${item.highlight}`} aria-pressed={activeSlide === index} onClick={() => setActiveSlide(index)} />)}</div></section>
        <DemoDataNotice /><div className="home-stats">{[[data.marketCount, 'marchés référencés', 'store'], [data.productCount, 'produits suivis', 'leaf'], [data.publishedCount, 'prix validés', 'chart-no-axes-column-increasing']].map(([count, label, icon]) => <article key={label}><span className="filter-orb"><Icon name={icon} /></span><div><strong>{count}</strong><small>{label}</small></div></article>)}</div>
        <div className="section-heading"><h2><Icon name="sparkles" className="section-icon" /> Derniers prix publiés</h2><a className="text-link" href={route.prices}>Tous les prix <Icon name="arrow-right" /></a></div>
        {data.observations.length ? <div className="price-grid">{data.observations.map((item) => <ObservationCard key={item.id} observation={item} />)}</div> : <div className="empty-state home-empty"><span className="empty-icon"><Icon name="chart-no-axes-column-increasing" /></span><h3>La collecte commence bientôt</h3><p>Les premiers prix validés apparaîtront ici. En attendant, explorez les marchés et les produits référencés.</p><a className="button button-primary" href={route.markets}>Voir les marchés</a></div>}
        <section className="home-chapter home-method" aria-labelledby="home-method-title"><div className="chapter-heading"><span className="chapter-eyebrow">Une information construite sur le terrain</span><h2 id="home-method-title">Du marché au repère utile</h2><p>Chaque observation suit un parcours simple avant d’être présentée aux acteurs du maraîchage.</p></div><div className="method-steps"><article className="method-step"><span className="step-number">01</span><span className="step-icon"><Icon name="store" /></span><div><h3>Observer</h3><p>Les prix sont relevés auprès des marchés maraîchers référencés.</p></div></article><article className="method-step"><span className="step-number">02</span><span className="step-icon"><Icon name="shield-check" /></span><div><h3>Vérifier</h3><p>L’équipe examine les informations avant leur publication.</p></div></article><article className="method-step"><span className="step-number">03</span><span className="step-icon"><Icon name="chart-no-axes-column-increasing" /></span><div><h3>Partager</h3><p>Les repères validés sont consultables par produit et par marché.</p></div></article></div></section>
        <section className="home-chapter home-context" aria-labelledby="home-context-title"><div className="context-copy"><span className="chapter-eyebrow">Lire les prix avec discernement</span><h2 id="home-context-title">Un prix observé donne un repère, pas une promesse.</h2><p>La qualité, le volume et les conditions de vente peuvent faire varier le prix final. Pour comparer, regardez toujours le produit, son unité, le marché et la date du relevé.</p><a className="text-link" href={route.about}>Comprendre notre démarche <Icon name="arrow-right" /></a></div><div className="context-points"><div><span className="context-point-icon"><Icon name="carrot" /></span><span><strong>Le produit</strong><small>Identifier ce qui a été relevé</small></span></div><div><span className="context-point-icon"><Icon name="store" /></span><span><strong>Le marché</strong><small>Situer le relevé</small></span></div><div><span className="context-point-icon"><Icon name="calendar-days" /></span><span><strong>La date et l’unité</strong><small>Lire le prix dans son contexte</small></span></div></div></section>
        <section className="home-chapter home-explore" aria-labelledby="home-explore-title"><div className="chapter-heading"><span className="chapter-eyebrow">Parcourir la plateforme</span><h2 id="home-explore-title">Choisissez votre point de départ</h2><p>Explorez les informations disponibles et retrouvez facilement les repères qui vous intéressent.</p></div><div className="home-discover"><a href={route.markets}><span className="filter-orb market-orb"><Icon name="map-pin" /></span><span><strong>Marchés du Bénin</strong><small>Repérez les lieux de collecte et de vente</small></span><Icon name="arrow-right" /></a><a href={route.products}><span className="filter-orb"><Icon name="carrot" /></span><span><strong>Produits maraîchers</strong><small>Parcourez les cultures suivies</small></span><Icon name="arrow-right" /></a><a href={route.prices}><span className="filter-orb"><Icon name="chart-no-axes-column-increasing" /></span><span><strong>Prix observés</strong><small>Comparez les relevés publiés</small></span><Icon name="arrow-right" /></a></div></section></>;
}

function PageIntro({ eyebrow, title, text, page, count }) {
    return <header className={`page-intro page-intro-${page}`}><div className="eyebrow">{eyebrow}</div><h1>{title}</h1><p>{text}</p>{count !== undefined && <div className="intro-count"><Icon name="chart-no-axes-column-increasing" /> {count} relevé(s) publié(s)</div>}</header>;
}

function Pagination({ currentPage, lastPage, url }) {
    if (lastPage < 2) return null;
    const pageUrl = (page) => {
        const target = new URL(window.location.href);
        target.searchParams.set('page', page);
        return `${target.pathname}?${target.searchParams.toString()}`;
    };
    return <nav className="pagination-wrap pagination-controls" aria-label="Pagination"><a className={`button button-light ${currentPage <= 1 ? 'disabled' : ''}`} href={currentPage > 1 ? pageUrl(currentPage - 1) : undefined} aria-disabled={currentPage <= 1}>Précédent</a><span>Page {currentPage} sur {lastPage}</span><a className={`button button-light ${currentPage >= lastPage ? 'disabled' : ''}`} href={currentPage < lastPage ? pageUrl(currentPage + 1) : undefined} aria-disabled={currentPage >= lastPage}>Suivant</a></nav>;
}

function PricesPage({ data }) {
    const currentQuery = new URLSearchParams(window.location.search);
    return <><PageIntro page="prices" eyebrow="Suivi des marchés · Bénin" title="Prix du jour" text="Comparez les prix maraîchers publiés et filtrez-les par produit et par marché." count={data.total} /><DemoDataNotice />
        {data.alertStatus && <div className="flash-success">{data.alertStatus}</div>}{data.alertError && <div className="flash-error">{data.alertError}</div>}
        <form id="filters" className="filter-bar filter-bar-featured" method="GET" action={route.prices}><label id="product-filter" className="filter-field"><span className="filter-orb"><Icon name="carrot" /></span><span>Produit<select name="product" defaultValue={data.selectedProduct || ''}><option value="">Tous les produits</option>{data.products.map((product) => <option key={product.id} value={product.id}>{product.name}</option>)}</select></span></label><label id="market-filter" className="filter-field"><span className="filter-orb market-orb"><Icon name="store" /></span><span>Marché<select name="market" defaultValue={data.selectedMarket || ''}><option value="">Tous les marchés</option>{data.markets.map((market) => <option key={market.id} value={market.id}>{market.name}{market.commune ? ` · ${market.commune}` : ''}</option>)}</select></span></label>{currentQuery.get('q') && <input type="hidden" name="q" value={currentQuery.get('q')} />}<button className="button button-primary filter-submit" type="submit"><Icon name="search" /> Afficher les prix</button></form>
        <section className="notice"><strong>Recevoir une alerte de prix</strong><p>Choisissez un produit et, si besoin, un prix plafond. Un message vous sera envoyé lorsqu’un nouveau relevé validé correspondra à vos critères.</p>{config.user && !config.user.isAdmin ? <form method="POST" action={route.priceAlerts} className="filter-bar"><input type="hidden" name="_token" value={config.csrf} /><label className="filter-field"><span>Produit<select name="product_id" required defaultValue={data.selectedProduct || ''}><option value="">Choisir un produit</option>{data.products.map((product) => <option key={product.id} value={product.id}>{product.name}</option>)}</select></span></label><label className="filter-field"><span>Marché<select name="market_id" defaultValue={data.selectedMarket || ''}><option value="">Tous les marchés</option>{data.markets.map((market) => <option key={market.id} value={market.id}>{market.name}</option>)}</select></span></label><label className="filter-field"><span>Prix maximum (FCFA)<input type="number" name="target_price_xof" min="1" placeholder="Tous les nouveaux prix" /></span></label><label className="filter-field"><span>Téléphone au format international<input type="tel" name="phone" required placeholder="+22901XXXXXXXX" pattern="\+[1-9][0-9]{7,14}" /></span></label><label className="consent-check"><input type="checkbox" name="notification_consent" value="1" required /> J’accepte de recevoir une alerte SMS ou WhatsApp.</label><button className="button button-primary" type="submit">Créer l’alerte</button></form> : <a className="button button-light" href={route.login}>Connectez-vous pour créer une alerte</a>}</section>
        <div id="prices" className="section-heading"><h2><Icon name="chart-no-axes-column-increasing" className="section-icon" /> Prix observés</h2><span><Icon name="calendar-days" />　Mis à jour selon les collectes terrain</span></div>
        {data.items.length ? <div className="price-grid">{data.items.map((item) => <ObservationCard key={item.id} observation={item} />)}</div> : <EmptyState title="Aucun relevé publié pour le moment" text="Les prix apparaîtront ici après la première collecte et validation par l’équipe." />}
        <div className="section-heading"><h2><Icon name="chart-no-axes-combined" className="section-icon" /> Tendances des marchés</h2><span>Volumes et évolution sur les 30 derniers jours</span></div><div className="table-wrap"><table><thead><tr><th>Produit</th><th>Marché</th><th>Offre disponible</th><th>Demande déclarée</th><th>Prix moyen</th><th>Évolution du prix</th></tr></thead><tbody>{data.trends.length ? data.trends.map((trend, index) => <tr key={trend.product + trend.market + index}><td>{trend.product}<small>{trend.request_count} demande(s) suivie(s)</small></td><td>{trend.market}</td><td>{trend.offer_quantity ? new Intl.NumberFormat('fr-FR').format(trend.offer_quantity) + ' ' + (trend.unit || '') : 'Non renseignée'}</td><td>{trend.demand_quantity ? new Intl.NumberFormat('fr-FR').format(trend.demand_quantity) + ' ' + (trend.unit || '') : 'Non renseignée'}</td><td>{trend.average_price ? new Intl.NumberFormat('fr-FR').format(trend.average_price) + ' FCFA' : '—'}</td><td>{trend.price_change_percent === null ? 'Historique insuffisant' : (trend.price_change_percent >= 0 ? '↑ ' : '↓ ') + Math.abs(trend.price_change_percent) + ' %'}</td></tr>) : <tr><td colSpan="6">Les tendances seront visibles après les premières quantités chiffrées et fiches validées.</td></tr>}</tbody></table></div>
        <Pagination currentPage={data.currentPage} lastPage={data.lastPage} />
        <section className="notice"><strong>À savoir :</strong> ce sont des prix observés, pas des prix garantis. Le prix final peut dépendre de la qualité, du volume et des conditions de vente.</section></>;
}

function DirectoryPage({ data, type }) {
    const markets = type === 'markets';
    const title = markets ? 'Les marchés' : 'Les produits';
    const pageUrl = markets ? route.markets : route.products;
    return <><PageIntro page={type} eyebrow={markets ? 'Ancrage local · Bénin' : 'Cultures locales · Bénin'} title={title} text={markets ? 'Retrouvez les marchés maraîchers référencés et les relevés qui y sont publiés.' : 'Découvrez les produits suivis par les collectes et consultez leurs prix observés.'} /><DemoDataNotice />
        <form className="directory-search" method="GET" action={pageUrl}><Icon name="search" /><input type="search" name="q" defaultValue={data.query || ''} placeholder={markets ? 'Rechercher un marché, une commune...' : 'Rechercher un produit ou une catégorie...'} /><button className="button button-primary" type="submit">Rechercher</button></form>
        <div className="section-heading"><h2><Icon name={markets ? 'store' : 'leaf'} className="section-icon" /> {markets ? 'Marchés référencés' : 'Catalogue maraîcher'}</h2><span>{data.total} {markets ? 'marché(s)' : 'produit(s)'}</span></div>
        {data.items.length ? <div className={`directory-grid ${markets ? '' : 'product-directory'}`}>{data.items.map((item) => <article className="directory-card" key={item.id}>{!markets && <div className="product-photo-frame directory-product-photo"><img src={productImage(item.name)} alt={item.name} loading="lazy" /><span className="product-image-category">{item.category || 'Produit local'}</span></div>}<div className="directory-card-top"><span className={`filter-orb ${markets ? 'market-orb' : ''}`}><Icon name={markets ? 'store' : item.category === 'Feuilles' ? 'salad' : 'carrot'} /></span><span className="status-pill">{item.published_observations_count} relevé(s)</span></div>{!markets && <p className="eyebrow product-category">{item.category || 'Produit local'}</p>}<h3>{item.name}</h3><p className="location">{markets ? [item.commune, item.department].filter(Boolean).join(' · ') || 'Bénin' : `Unité de référence : ${item.unit}`}</p><div className="directory-card-foot"><span><Icon name="chart-no-axes-column-increasing" /> Prix observés</span><a href={`${route.prices}?${markets ? 'market' : 'product'}=${item.id}`}>{markets ? 'Voir les prix' : 'Consulter'} <Icon name="arrow-right" /></a></div></article>)}</div> : <EmptyState title={`Aucun ${markets ? 'marché' : 'produit'} trouvé`} text="Essayez avec un autre nom ou un autre terme de recherche." icon={markets ? 'map-pin' : 'leaf'} />}
        <Pagination currentPage={data.currentPage} lastPage={data.lastPage} /></>;
}

function AboutPage({ data }) {
    return <><section className="about-hero"><div className="hero-tag"><Icon name="map-pin" /> Information de marché · Bénin</div><h1>Des prix plus lisibles,<br /><em>des décisions mieux éclairées.</em></h1><p>Cette plateforme partage des observations de prix maraîchers collectées sur les marchés du Bénin pour aider les acteurs à mieux se repérer.</p><a className="button button-light" href={route.prices}>Découvrir les prix publiés <Icon name="arrow-right" /></a></section><div className="home-stats">{[[data.publishedCount, 'relevés publiés', 'chart-no-axes-column-increasing'], [data.marketCount, 'marchés référencés', 'store'], [data.productCount, 'produits suivis', 'leaf']].map(([count, label, icon]) => <article key={label}><span className="filter-orb"><Icon name={icon} /></span><div><strong>{count}</strong><small>{label}</small></div></article>)}</div><div className="section-heading"><h2><Icon name="shield-check" className="section-icon" /> Comment utiliser l’information</h2></div><div className="about-grid"><article><span className="about-number">01</span><h3>Comparer</h3><p>Choisissez un produit et un marché pour consulter les derniers prix relevés et validés.</p></article><article><span className="about-number">02</span><h3>Interpréter</h3><p>Chaque prix est associé à une unité, un lieu et une date de collecte. Ces éléments donnent son contexte.</p></article><article><span className="about-number">03</span><h3>Prendre contact</h3><p>Les acheteurs peuvent demander une mise en relation avec l’équipe à partir d’un relevé publié.</p></article></div><section className="notice"><strong>À savoir :</strong> les prix affichés sont des observations à la date de collecte. Ce ne sont pas des prix garantis ; la qualité, le volume et les conditions de vente peuvent faire varier le prix final.</section></>;
}

function DetailPage({ data }) {
    const item = data.observation;
    return <><a className="back-link" href={route.prices}><Icon name="arrow-left" /> Retour aux prix</a><DemoDataNotice />{data.error && <div className="flash-error">{data.error}</div>}<div className="detail-grid"><section className="detail-card"><img className="detail-product-photo" src={productImage(item.product.name)} alt={item.product.name} /><div className="eyebrow">Prix observé · {item.market.name}</div><h1>{item.product.name}</h1><p className="location">{item.market.commune}{item.market.department ? ` · ${item.market.department}` : ''}</p><div className="detail-price">{new Intl.NumberFormat('fr-FR').format(item.price_xof)} <small>FCFA</small></div><p>par {item.unit_snapshot}</p><dl className="facts"><div><dt>Collecté le</dt><dd>{item.observed_at}</dd></div><div><dt>Qualité indiquée</dt><dd>{item.quality || 'Non précisée'}</dd></div><div><dt>Offre déclarée</dt><dd>{item.available_quantity ? `${new Intl.NumberFormat('fr-FR').format(item.available_quantity)} ${item.unit_snapshot}` : 'Non précisée'}</dd></div><div><dt>Marché</dt><dd>{item.market.name}</dd></div></dl></section><section className="detail-card contact-card"><h2>Demander une mise en relation</h2><p>L’équipe examine chaque demande avant de transmettre les coordonnées d’un producteur.</p>{config.user ? data.hasUndeclaredTransaction ? <div className="notice">Déclarez le résultat de votre dernière mise en relation dans « Mes demandes » avant d’en envoyer une nouvelle.</div> : <form method="POST" action={`${route.contactPrefix}/${item.id}/demande`} className="stack-form"><input type="hidden" name="_token" value={config.csrf} /><label>Organisation (facultatif)<input name="organization" placeholder="Nom de votre structure" /></label><label>Téléphone de contact<input name="phone" required placeholder="+22901XXXXXXXX" pattern="\+[1-9][0-9]{7,14}" /></label><label>Quantité recherchée<input type="number" name="requested_quantity" min="0.001" step="0.001" placeholder={`Quantité en ${item.unit_snapshot}`} /></label><label>Période ou précision du volume<input name="requested_volume" placeholder="Ex. cette semaine, livraison souhaitée…" /></label><label>Message (facultatif)<textarea name="message" rows="3" /></label><label className="consent-check"><input type="checkbox" name="notification_consent" value="1" required /> J’accepte de recevoir un message lié au suivi de cette demande.</label><button className="button button-primary" type="submit">Envoyer la demande</button></form> : <div className="login-callout"><p>Connectez-vous ou créez un compte acheteur pour envoyer une demande.</p><a className="button button-primary" href={route.login}>Se connecter</a><a className="text-link" href={route.register}>Créer un compte</a></div>}</section></div></>;
}

const statusLabels = { pending: 'En attente', approved: 'Approuvée', rejected: 'Refusée', completed: 'Terminée' };

function RequestsPage({ data }) {
    return <><PageIntro page="requests" eyebrow="Espace acheteur" title="Mes demandes" text="Suivez le traitement de vos demandes de mise en relation et gérez vos alertes de prix." />
        {data.statusMessage && <div className="flash-success">{data.statusMessage}</div>}{data.error && <div className="flash-error">{data.error}</div>}
        <section className="admin-section"><div className="section-heading"><h2>Mes alertes de prix</h2><a className="text-link" href={route.prices}>Créer une alerte <Icon name="arrow-right" /></a></div>{data.priceAlerts?.length ? <div className="request-list">{data.priceAlerts.map((alert) => <article className="request-card" key={alert.id}><div><h3>{alert.product} · {alert.market || 'Tous les marchés'}</h3><p>{alert.target_price_xof ? `Prix plafond : ${new Intl.NumberFormat('fr-FR').format(alert.target_price_xof)} FCFA` : 'À chaque nouveau prix validé'} · {alert.phone}</p></div><form method="POST" action={route.priceAlerts + '/' + alert.id}><input type="hidden" name="_token" value={config.csrf} /><input type="hidden" name="_method" value="DELETE" /><button className="button button-light" type="submit">Supprimer</button></form></article>)}</div> : <p>Vous n’avez pas encore créé d’alerte de prix.</p>}</section>
        {data.items.length ? <div className="request-list">{data.items.map((item) => <article className="request-card animated-card" key={item.id}>
            <div><span className="eyebrow">{item.created_at}</span><h3>{item.product} · {item.market}</h3><p>{item.requested_volume || 'Volume non précisé'}</p></div>
            <div><span className={`request-status status-${item.status}`}>{statusLabels[item.status] || item.status}</span>
                {item.producer && <p className="producer-contact"><b>{item.producer.name}</b><br />{item.producer.phone}</p>}
                {item.status === 'approved' && !item.transaction_declared_at ? <form method="POST" action={`${route.transactionPrefix}/${item.id}/transaction`} className="declare-form"><input type="hidden" name="_token" value={config.csrf} /><input type="hidden" name="_method" value="PATCH" /><label>La vente a-t-elle eu lieu ?<select name="transaction_happened" required defaultValue=""><option value="">Choisir une réponse</option><option value="1">Oui, la vente a eu lieu</option><option value="0">Non, pas de vente</option></select></label><button className="button button-primary" type="submit">Déclarer</button></form> : item.transaction_declared_at && <p className="producer-contact">Déclaration envoyée le {item.transaction_declared_at} : {item.transaction_happened ? 'vente réalisée' : 'pas de vente'}</p>}
            </div></article>)}</div> : <EmptyState title="Vous n’avez pas encore de demande" text="Choisissez un prix observé pour contacter un producteur." icon="mail" />}
        <Pagination currentPage={data.currentPage} lastPage={data.lastPage} />
    </>;
}

function AdminPage({ data }) {
    const [activeSection, setActiveSection] = useState('admin-overview');
    const [requestTab, setRequestTab] = useState('all');
    const [requestSearch, setRequestSearch] = useState('');
    const requests = data.requests || data.pendingRequests;
    const filteredRequests = requests.filter((item) => {
        const matchesTab = requestTab === 'all' || item.status === requestTab;
        const searchable = `${item.product} ${item.buyer} ${item.phone} ${item.market}`.toLocaleLowerCase('fr-FR');
        return matchesTab && searchable.includes(requestSearch.toLocaleLowerCase('fr-FR'));
    });
    const requestTabs = [
        ['all', 'Toutes les demandes', data.allRequestCount ?? requests.length],
        ['pending', 'En attente', data.requestCount ?? requests.filter((item) => item.status === 'pending').length],
        ['approved', 'Approuvées', data.approvedRequestCount ?? requests.filter((item) => item.status === 'approved').length],
        ['rejected', 'Refusées', data.rejectedRequestCount ?? requests.filter((item) => item.status === 'rejected').length],
    ];
    const titles = { 'admin-overview': 'Tableau de bord', 'admin-requests': 'Demandes', 'admin-prices': 'Prix du jour', 'admin-products': 'Produits', 'admin-markets': 'Marchés', 'admin-history': 'Historique', 'admin-trends': 'Offre, demande & tendances', 'admin-notifications': 'Notifications' };
    useEffect(() => {
        const syncSection = () => {
            const section = window.location.hash.slice(1);
            if (Object.prototype.hasOwnProperty.call(titles, section)) setActiveSection(section);
        };
        syncSection();
        window.addEventListener('hashchange', syncSection);
        window.addEventListener('popstate', syncSection);
        return () => {
            window.removeEventListener('hashchange', syncSection);
            window.removeEventListener('popstate', syncSection);
        };
    }, []);
    const changeSection = (section) => (event) => {
        event.preventDefault();
        setActiveSection(section);
        window.history.pushState({ adminSection: section }, '', `#${section}`);
    };
    const hiddenToken = <input type="hidden" name="_token" value={config.csrf} />;
    const field = (label, child, extra = '') => <label className={extra}>{label}{child}</label>;
    return <div className="admin-console">
        <aside className="admin-sidebar" aria-label="Navigation de l’administration">
            <a className="admin-side-brand" href="#admin-overview" onClick={changeSection('admin-overview')}><span className="admin-brand-mark"><Icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Administration</small></span></a>
            <span className="admin-side-caption">MENU PRINCIPAL</span>
            <nav className="admin-side-nav">
                <a className={`admin-side-link ${activeSection === 'admin-overview' ? 'is-active' : ''}`} href="#admin-overview" aria-current={activeSection === 'admin-overview' ? 'page' : undefined} onClick={changeSection('admin-overview')}><Icon name="chart-no-axes-column-increasing" />Tableau de bord</a>
                <a className={`admin-side-link ${activeSection === 'admin-requests' ? 'is-active' : ''}`} href="#admin-requests" aria-current={activeSection === 'admin-requests' ? 'page' : undefined} onClick={changeSection('admin-requests')}><Icon name="store" />Demandes <b>{data.requestCount}</b></a>
                <a className={`admin-side-link ${activeSection === 'admin-prices' ? 'is-active' : ''}`} href="#admin-prices" aria-current={activeSection === 'admin-prices' ? 'page' : undefined} onClick={changeSection('admin-prices')}><Icon name="chart-no-axes-column-increasing" />Prix du jour {data.pendingPriceCount > 0 && <b>{data.pendingPriceCount}</b>}</a>
                <a className={`admin-side-link ${activeSection === 'admin-products' ? 'is-active' : ''}`} href="#admin-products" aria-current={activeSection === 'admin-products' ? 'page' : undefined} onClick={changeSection('admin-products')}><Icon name="leaf" />Produits</a>
                <a className={`admin-side-link ${activeSection === 'admin-markets' ? 'is-active' : ''}`} href="#admin-markets" aria-current={activeSection === 'admin-markets' ? 'page' : undefined} onClick={changeSection('admin-markets')}><Icon name="store" />Marchés</a>
                <a className={`admin-side-link ${activeSection === 'admin-history' ? 'is-active' : ''}`} href="#admin-history" aria-current={activeSection === 'admin-history' ? 'page' : undefined} onClick={changeSection('admin-history')}><Icon name="calendar-days" />Historique</a>
                <a className={`admin-side-link ${activeSection === 'admin-trends' ? 'is-active' : ''}`} href="#admin-trends" aria-current={activeSection === 'admin-trends' ? 'page' : undefined} onClick={changeSection('admin-trends')}><Icon name="chart-no-axes-combined" />Tendances</a>
                <a className={`admin-side-link ${activeSection === 'admin-notifications' ? 'is-active' : ''}`} href="#admin-notifications" aria-current={activeSection === 'admin-notifications' ? 'page' : undefined} onClick={changeSection('admin-notifications')}><Icon name="bell" />Notifications</a>
            </nav>
            <div className="admin-side-foot"><span className="admin-brand-mark"><Icon name="sprout" /></span><span><strong>Des prix plus justes</strong><small>Pour les marchés du Bénin</small></span></div>
        </aside>
        <div className="admin-content" id="admin-overview">
        <header className="admin-titlebar"><div><h1>{titles[activeSection]}</h1>{activeSection === 'admin-requests' && <span className="admin-found-count">{filteredRequests.length} demandes trouvées</span>}</div><div className="admin-panel-actions"><button className="admin-tool-button" type="button" aria-label="Voir les notifications" onClick={() => setActiveSection('admin-notifications')}><Icon name="bell" /></button>{activeSection === 'admin-requests' && <label className="admin-search-box"><Icon name="search" /><input value={requestSearch} onChange={(event) => setRequestSearch(event.target.value)} placeholder="Rechercher une demande…" aria-label="Rechercher une demande" /></label>}<span className="admin-user-avatar" title={config.user?.name || 'Administrateur'}>{(config.user?.name || 'A').slice(0, 1).toLocaleUpperCase('fr-FR')}</span></div></header>
        {data.statusMessage && <div className="flash-success">{data.statusMessage}</div>}{data.hasErrors && <div className="flash-error">Veuillez vérifier les champs signalés dans les formulaires.</div>}
        {activeSection === 'admin-requests' && <><nav className="admin-tabs" aria-label="Filtres des demandes">{requestTabs.map(([key, label, count]) => <button type="button" key={key} className={requestTab === key ? 'is-active' : ''} aria-pressed={requestTab === key} onClick={() => setRequestTab(key)}>{label} <b>{count}</b></button>)}</nav><section className="admin-section admin-requests-section" id="admin-requests"><div className="section-heading"><h2>{requestTab === 'all' ? 'Toutes les demandes' : requestTab === 'pending' ? 'Demandes en attente' : requestTab === 'approved' ? 'Demandes approuvées' : 'Demandes refusées'}</h2><span>Les coordonnées sont révélées après approbation</span></div>
            <div className="table-wrap"><table><thead><tr><th>Référence</th><th>Produit · acheteur</th><th>Marché</th><th>Date</th><th>Volume</th><th>Statut</th><th>Action</th></tr></thead><tbody>{filteredRequests.length ? filteredRequests.map((item) => <tr key={item.id}><td>#{item.id}</td><td><strong>{item.product}</strong><small>{item.buyer} · {item.phone}</small></td><td>{item.market}</td><td>{item.created_at || '—'}</td><td>{item.requested_quantity ? new Intl.NumberFormat('fr-FR').format(item.requested_quantity) + ' ' + (item.requested_unit_snapshot || '') : item.requested_volume || 'À préciser'}</td><td><span className={`request-status status-${item.status}`}>{({ pending: 'En attente', approved: 'Approuvée', rejected: 'Refusée' })[item.status] || item.status}</span></td><td>{item.status === 'pending' ? <details className="admin-action-menu"><summary aria-label={`Gérer la demande ${item.id}`}><Icon name="chevron-down" /></summary><form method="POST" action={`${route.adminReviewPrefix}/${item.id}`} className="admin-action-form">{hiddenToken}<input type="hidden" name="_method" value="PATCH" />{field('Producteur à contacter', <select name="producer_id"><option value="">Choisir un producteur</option>{data.producers.map((producer) => <option key={producer.id} value={producer.id}>{producer.name} · {producer.phone}</option>)}</select>)}{field('Note interne', <input name="admin_note" placeholder="Information de suivi" />)}<div className="inline-actions"><button className="button button-primary" name="decision" value="approved">Approuver</button><button className="button button-danger" name="decision" value="rejected">Refuser</button></div></form></details> : '—'}</td></tr>) : <tr className="admin-empty-row"><td colSpan="7"><div className="admin-table-empty"><span><Icon name="search" /></span><strong>{requestSearch ? 'Aucun résultat' : 'Aucune demande dans cette catégorie'}</strong><small>{requestSearch ? 'Modifiez votre recherche puis réessayez.' : 'Les nouvelles demandes apparaîtront ici.'}</small></div></td></tr>}</tbody></table></div>
        </section></>}
        {activeSection === 'admin-prices' && <section className="admin-section"><div className="section-heading"><h2>Saisir un relevé de prix</h2><span>Chaque fiche est vérifiée avant publication</span></div>{(!data.products.length || !data.markets.length) && <div className="notice">Ajoutez d’abord un produit et un marché dans les rubriques correspondantes.</div>}
            <form method="POST" action={route.adminPrices} className="admin-form">{hiddenToken}{field('Produit', <select name="product_id" required defaultValue=""><option value="">Choisir</option>{data.products.map((product) => <option key={product.id} value={product.id}>{product.name} · {product.unit}</option>)}</select>)}{field('Marché', <select name="market_id" required defaultValue=""><option value="">Choisir</option>{data.markets.map((market) => <option key={market.id} value={market.id}>{market.name}{market.commune ? ` · ${market.commune}` : ''}</option>)}</select>)}{field('Producteur (facultatif)', <select name="producer_id"><option value="">Non associé</option>{data.producers.map((producer) => <option key={producer.id} value={producer.id}>{producer.name}</option>)}</select>)}{field('Prix (FCFA)', <input type="number" name="price_xof" min="1" required placeholder="Ex. 18000" />)}{field('Quantité disponible', <input type="number" name="available_quantity" min="0.001" step="0.001" placeholder="Facultatif" />)}{field('Qualité / état', <input name="quality" placeholder="Ex. qualité standard" />)}{field('Date et heure', <input type="datetime-local" name="observed_at" max={data.nowLocal} defaultValue={data.nowLocal} required />)}{field('Note de collecte', <input name="notes" placeholder="Facultatif" />, 'wide-field')}<button className="button button-primary" type="submit">Enregistrer pour validation</button></form>
            <div className="section-heading admin-subheading"><h2>Fiches prix à valider</h2><span>{data.pendingPrices.length} en attente</span></div><div className="table-wrap"><table><thead><tr><th>Produit · marché</th><th>Prix</th><th>Offre</th><th>Collecte</th><th>Validation</th></tr></thead><tbody>{data.pendingPrices.length ? data.pendingPrices.map((price) => <tr key={price.id}><td><strong>{price.product}</strong><small>{price.market}{price.producer ? ' · ' + price.producer : ''}</small></td><td>{new Intl.NumberFormat('fr-FR').format(price.price_xof)} FCFA / {price.unit_snapshot}</td><td>{price.available_quantity ? new Intl.NumberFormat('fr-FR').format(price.available_quantity) + ' ' + price.unit_snapshot : '—'}</td><td>{price.observed_at}<small>{price.collector}</small></td><td><form method="POST" action={route.adminReviewPricePrefix + '/' + price.id + '/validation'} className="inline-actions">{hiddenToken}<input type="hidden" name="_method" value="PATCH" /><button className="button button-primary" name="decision" value="published">Publier</button><button className="button button-danger" name="decision" value="rejected">Refuser</button></form></td></tr>) : <tr><td colSpan="5">Aucune fiche prix à traiter.</td></tr>}</tbody></table></div>
            <div className="section-heading admin-subheading"><h2>Relevés publiés aujourd’hui</h2><span>{data.todayPrices.length} relevé(s)</span></div><div className="table-wrap"><table><thead><tr><th>Produit</th><th>Marché</th><th>Prix</th><th>Offre</th><th>Qualité</th><th>Heure</th><th>Agent</th></tr></thead><tbody>{data.todayPrices.length ? data.todayPrices.map((price, index) => <tr key={`${price.observed_at}-${index}`}><td><strong>{price.product}</strong><small>{price.unit_snapshot}</small></td><td>{price.market}</td><td><b>{new Intl.NumberFormat('fr-FR').format(price.price_xof)} FCFA</b></td><td>{price.available_quantity ? new Intl.NumberFormat('fr-FR').format(price.available_quantity) + ' ' + price.unit_snapshot : '—'}</td><td>{price.quality || 'Standard'}</td><td>{price.observed_at}</td><td>{price.collector || '—'}</td></tr>) : <tr><td colSpan="7">Aucun relevé enregistré aujourd’hui.</td></tr>}</tbody></table></div>
        </section>}
        {(activeSection === 'admin-products' || activeSection === 'admin-markets') && <section className="admin-section" id={activeSection}><div className="section-heading"><h2>{activeSection === 'admin-products' ? 'Catalogue des produits' : 'Répertoire des marchés'}</h2><span>{activeSection === 'admin-products' ? `${data.products.length} produit(s) référencé(s)` : `${data.markets.length} marché(s) référencé(s)`}</span></div><div className="catalog-grid">
            {activeSection === 'admin-products' && <form method="POST" action={route.adminProducts} className="catalog-card">{hiddenToken}<h3>Ajouter un produit</h3>{field('Nom', <input name="name" required placeholder="Nom du produit" />)}{field('Catégorie', <input name="category" placeholder="Ex. légume-fruit" />)}{field('Unité de vente', <input name="unit" required placeholder="Ex. sac de 25 kg" />)}<button className="button button-light">Ajouter le produit</button></form>}
            {activeSection === 'admin-markets' && <form method="POST" action={route.adminMarkets} className="catalog-card">{hiddenToken}<h3>Ajouter un marché</h3>{field('Nom du marché', <input name="name" required placeholder="Nom du marché" />)}{field('Commune', <input name="commune" placeholder="Commune" />)}{field('Département', <input name="department" placeholder="Département" />)}<button className="button button-light">Ajouter le marché</button></form>}
            <form method="POST" action={route.adminProducers} className="catalog-card">{hiddenToken}<h3>Ajouter un producteur</h3>{field('Nom / groupement', <input name="name" required placeholder="Nom du producteur" />)}{field('Téléphone', <input name="phone" required placeholder="+22901XXXXXXXX" pattern="\\+[1-9][0-9]{7,14}" />)}{field('Marché', <select name="market_id"><option value="">À préciser</option>{data.markets.map((market) => <option key={market.id} value={market.id}>{market.name}</option>)}</select>)}<label className="consent-check"><input type="checkbox" name="notification_consent" value="1" /> Consentement du producteur pour recevoir des messages</label><button className="button button-light">Ajouter au répertoire</button></form>
            <section className="catalog-card admin-record-card"><div className="admin-record-heading"><h3>{activeSection === 'admin-products' ? 'Produits enregistrés' : 'Marchés enregistrés'}</h3><span>{activeSection === 'admin-products' ? data.products.length : data.markets.length}</span></div><div className="admin-record-list">{activeSection === 'admin-products' ? (data.products.length ? data.products.map((product) => <article className="admin-record-item" key={product.id}><div><strong>{product.name}</strong><small>{product.category || 'Produit maraîcher'} · {product.unit}</small></div><span className={`admin-record-status ${product.is_active ? 'is-active' : ''}`}>{product.is_active ? 'Actif' : 'Inactif'}</span></article>) : <p className="admin-record-empty">Aucun produit enregistré pour le moment.</p>) : (data.markets.length ? data.markets.map((market) => <article className="admin-record-item" key={market.id}><div><strong>{market.name}</strong><small>{[market.commune, market.department].filter(Boolean).join(' · ') || 'Localisation à préciser'}</small></div><span className={`admin-record-status ${market.is_active ? 'is-active' : ''}`}>{market.is_active ? 'Actif' : 'Inactif'}</span></article>) : <p className="admin-record-empty">Aucun marché enregistré pour le moment.</p>)}</div></section>
        </div><div className="admin-producer-directory"><div className="section-heading"><h2>Producteurs actifs</h2><span>{data.producers.length} dans le répertoire</span></div><div className="table-wrap"><table><thead><tr><th>Producteur / groupement</th><th>Téléphone</th><th>Marché</th><th>Commune</th></tr></thead><tbody>{data.producers.length ? data.producers.map((producer) => <tr key={producer.id}><td><strong>{producer.name}</strong></td><td>{producer.phone}</td><td>{producer.market || 'À préciser'}</td><td>{producer.commune || '—'}</td></tr>) : <tr><td colSpan="4">Aucun producteur actif dans le répertoire.</td></tr>}</tbody></table></div></div></section>}
        {activeSection === 'admin-overview' && <section className="admin-section"><div className="admin-summary-grid"><article className="catalog-card"><h3>{data.requestCount}</h3><p>demandes en attente</p></article><article className="catalog-card"><h3>{data.transactionCount}</h3><p>transactions déclarées</p></article></div><div className="inline-actions"><a className="button button-light" href={route.adminExportsPrefix + '/demandes'}>Exporter les demandes CSV</a><a className="button button-light" href={route.adminExportsPrefix + '/prix'}>Exporter les prix CSV</a></div><div className="notice"><strong>Rappels d’équipe :</strong> une alerte e-mail est programmée pour chaque demande encore en attente après 48 heures. Vérifiez l’adresse e-mail et l’envoi du serveur.</div><div className="section-heading"><h2>Dernières demandes</h2><span>Suivi de l’activité récente</span></div><div className="table-wrap"><table><thead><tr><th>Référence</th><th>Produit · acheteur</th><th>Marché</th><th>Date</th><th>Statut</th></tr></thead><tbody>{requests.length ? requests.slice(0, 8).map((item) => <tr key={item.id}><td>#{item.id}</td><td><strong>{item.product}</strong><small>{item.buyer}</small></td><td>{item.market}</td><td>{item.created_at || '—'}</td><td>{item.status}</td></tr>) : <tr><td colSpan="5">Aucune demande enregistrée.</td></tr>}</tbody></table></div></section>}
        {activeSection === 'admin-history' && <section className="admin-section"><div className="section-heading"><h2>Derniers relevés</h2><span>Historique récent</span></div><div className="table-wrap"><table><thead><tr><th>Produit</th><th>Marché</th><th>Prix</th><th>Collecte</th><th>Agent</th></tr></thead><tbody>{data.recentPrices.length ? data.recentPrices.map((price, index) => <tr key={`${price.observed_at}-${index}`}><td>{price.product}</td><td>{price.market}</td><td><b>{new Intl.NumberFormat('fr-FR').format(price.price_xof)} FCFA</b><small> / {price.unit_snapshot}</small></td><td>{price.observed_at}</td><td>{price.collector}</td></tr>) : <tr><td colSpan="5">Aucun relevé enregistré.</td></tr>}</tbody></table></div></section>}
        {activeSection === 'admin-trends' && <section className="admin-section"><div className="section-heading"><h2>Offre, demande et tendances</h2><a className="button button-light" href={route.adminExportsPrefix + '/prix'}>Exporter les prix CSV</a></div><p>Volumes et demandes enregistrés sur les 30 derniers jours. La tendance compare le prix moyen des 30 derniers jours aux 30 jours précédents.</p><div className="table-wrap"><table><thead><tr><th>Produit</th><th>Marché</th><th>Offre déclarée</th><th>Demande chiffrée</th><th>Demandes</th><th>Prix moyen récent</th><th>Évolution</th></tr></thead><tbody>{data.trends.length ? data.trends.map((trend, index) => <tr key={trend.product + trend.market + index}><td>{trend.product}</td><td>{trend.market}</td><td>{trend.offer_quantity ? new Intl.NumberFormat('fr-FR').format(trend.offer_quantity) + ' ' + (trend.unit || '') : 'Non renseignée'}</td><td>{trend.demand_quantity ? new Intl.NumberFormat('fr-FR').format(trend.demand_quantity) + ' ' + (trend.unit || '') : 'Non renseignée'}</td><td>{trend.request_count}</td><td>{trend.average_price ? new Intl.NumberFormat('fr-FR').format(trend.average_price) + ' FCFA' : '—'}</td><td>{trend.price_change_percent === null ? 'Historique insuffisant' : (trend.price_change_percent >= 0 ? '↑ ' : '↓ ') + Math.abs(trend.price_change_percent) + ' %'}</td></tr>) : <tr><td colSpan="7">Les tendances apparaîtront après les premières fiches validées et demandes.</td></tr>}</tbody></table></div><p className="notice">Les volumes d’offre et de demande sont calculés à partir des quantités numériques saisies. Les anciennes fiches sans quantité restent visibles dans l’historique des prix.</p></section>}
        {activeSection === 'admin-notifications' && <section className="admin-section" id="admin-notifications"><div className="section-heading"><h2>Notifications</h2><span>Journal des messages et suivis</span></div>{!data.notificationReady && <div className="notice"><strong>Canal actuel : {data.notificationChannel}.</strong> Les messages sont journalisés et ne sont pas envoyés. Pour activer les envois, configurez le compte Twilio, le numéro expéditeur SMS ou WhatsApp et, pour WhatsApp, le modèle approuvé.</div>}<div className="table-wrap"><table><thead><tr><th>Événement</th><th>Destinataire</th><th>Canal</th><th>État</th><th>Action</th></tr></thead><tbody>{data.recentNotifications.length ? data.recentNotifications.map((item) => <tr key={item.id}><td>{item.event}<small>{item.subject}</small></td><td>{item.recipient_name} · {item.recipient_phone}</td><td>{item.channel}</td><td><span className={`request-status status-${item.status}`}>{item.status}</span>{item.has_error && <small> · configuration à vérifier</small>}</td><td>{item.can_retry && <form method="POST" action={`${route.adminRetryPrefix}/${item.id}/retry`}>{hiddenToken}<button className="button button-light" type="submit">Réessayer</button></form>}</td></tr>) : <tr><td colSpan="5">Aucune notification générée.</td></tr>}</tbody></table></div></section>}
        </div>
    </div>;
}

function AuthPage({ page, data }) {
    const [useRecovery, setUseRecovery] = useState(false);
    const authTheme = localStorage.getItem('market-theme') === 'dark' ? 'dark' : 'light';
    useEffect(() => { document.body.dataset.theme = authTheme; }, [authTheme]);
    const [passkeysSupported, setPasskeysSupported] = useState(Boolean(window.Passkeys?.isSupported()));
    const [passkeyLoading, setPasskeyLoading] = useState(false);
    const [passkeyError, setPasskeyError] = useState('');
    useEffect(() => {
        const ready = () => setPasskeysSupported(Boolean(window.Passkeys?.isSupported()));
        window.addEventListener('passkeys:ready', ready, { once: true });
        return () => window.removeEventListener('passkeys:ready', ready);
    }, []);
    const signInWithPasskey = async () => {
        setPasskeyLoading(true);
        setPasskeyError('');
        try {
            const response = await window.Passkeys.verify({ routes: { options: '/passkeys/login/options', submit: '/passkeys/login' } });
            window.location.assign(response.redirect || '/dashboard');
        } catch (error) {
            if (error.constructor?.name !== 'UserCancelledError') setPasskeyError(error.message || 'La connexion par clé d’accès a échoué.');
        } finally {
            setPasskeyLoading(false);
        }
    };
    const fieldError = (name) => data.errors?.[name]?.[0] && <small className="auth-field-error">{data.errors[name][0]}</small>;
    const csrf = <input type="hidden" name="_token" value={config.csrf} />;
    const titles = {
        'auth-login': ['Bienvenue', 'Connectez-vous pour suivre vos demandes et accéder à votre espace.'],
        'auth-register': ['Créer un compte acheteur', 'Rejoignez la plateforme pour demander une mise en relation.'],
        'auth-forgot': ['Mot de passe oublié ?', 'Recevez un lien sécurisé pour choisir un nouveau mot de passe.'],
        'auth-reset': ['Choisir un nouveau mot de passe', 'Votre nouveau mot de passe protégera votre compte.'],
        'auth-verify': ['Vérifiez votre adresse e-mail', 'Un lien de vérification vous a été envoyé après votre inscription.'],
        'auth-two-factor': [useRecovery ? 'Code de récupération' : 'Vérification en deux étapes', useRecovery ? 'Saisissez l’un de vos codes de secours.' : 'Saisissez le code affiché dans votre application d’authentification.'],
        'auth-confirm': ['Confirmez votre mot de passe', 'Cette vérification protège les réglages sensibles de votre compte.'],
    };
    const [heading, description] = titles[page] || titles['auth-login'];
    let form;
    if (page === 'auth-login') form = <div className="auth-form">{passkeysSupported && <><button className="button button-light" type="button" onClick={signInWithPasskey} disabled={passkeyLoading}>{passkeyLoading ? 'Connexion en cours…' : 'Se connecter avec une clé d’accès'}</button>{passkeyError && <small className="auth-field-error">{passkeyError}</small>}<div className="auth-divider"><span>ou avec votre identifiant</span></div></>}<form method="POST" action={route.loginStore} className="auth-form">{csrf}<label>Identifiant ou adresse e-mail<input name="email" type="text" required autoComplete="username" autoFocus defaultValue={data.email} placeholder="admin ou nom@exemple.com" />{fieldError('email')}</label><label>Mot de passe<input name="password" type="password" required autoComplete="current-password" placeholder="Votre mot de passe" />{fieldError('password')}</label><div className="auth-form-row"><label className="auth-check"><input name="remember" type="checkbox" value="1" /> Se souvenir de moi</label><a href={route.passwordRequest}>Mot de passe oublié ?</a></div><button className="button button-primary" type="submit">Se connecter</button><p className="auth-switch">Pas encore de compte ? <a href={route.register}>Créer mon compte</a></p></form></div>;
    else if (page === 'auth-register') form = <form method="POST" action={route.registerStore} className="auth-form">{csrf}<label>Nom complet<input name="name" required autoComplete="name" autoFocus defaultValue={data.name} placeholder="Votre nom" />{fieldError('name')}</label><label>Adresse e-mail<input name="email" type="email" required autoComplete="email" defaultValue={data.email} placeholder="nom@exemple.com" />{fieldError('email')}</label><label>Mot de passe<input name="password" type="password" required minLength="8" autoComplete="new-password" placeholder="8 caractères minimum" />{fieldError('password')}</label><label>Confirmer le mot de passe<input name="password_confirmation" type="password" required minLength="8" autoComplete="new-password" placeholder="Répétez le mot de passe" />{fieldError('password')}</label><button className="button button-primary" type="submit">Créer mon compte</button><p className="auth-switch">Déjà inscrit ? <a href={route.login}>Se connecter</a></p></form>;
    else if (page === 'auth-forgot') form = <form method="POST" action={route.passwordEmail} className="auth-form">{csrf}<label>Adresse e-mail<input name="email" type="email" required autoFocus defaultValue={data.email} placeholder="nom@exemple.com" />{fieldError('email')}</label><button className="button button-primary" type="submit">Recevoir le lien de réinitialisation</button><p className="auth-switch"><a href={route.login}>Retour à la connexion</a></p></form>;
    else if (page === 'auth-reset') form = <form method="POST" action={route.passwordUpdate} className="auth-form">{csrf}<input type="hidden" name="token" value={data.token || ''} /><label>Adresse e-mail<input name="email" type="email" required defaultValue={data.email} />{fieldError('email')}</label><label>Nouveau mot de passe<input name="password" type="password" required minLength="8" autoComplete="new-password" />{fieldError('password')}</label><label>Confirmer le mot de passe<input name="password_confirmation" type="password" required minLength="8" autoComplete="new-password" />{fieldError('password')}</label><button className="button button-primary" type="submit">Réinitialiser le mot de passe</button></form>;
    else if (page === 'auth-verify') form = <div className="auth-form"><form method="POST" action={route.verificationSend}>{csrf}<button className="button button-primary" type="submit">Renvoyer le lien de vérification</button></form><form method="POST" action={route.logout}>{csrf}<button className="auth-logout" type="submit">Se déconnecter</button></form></div>;
    else if (page === 'auth-two-factor') form = <form method="POST" action={route.twoFactorStore} className="auth-form">{csrf}{useRecovery ? <label>Code de récupération<input name="recovery_code" required autoFocus autoComplete="one-time-code" />{fieldError('recovery_code')}</label> : <label>Code d’authentification<input name="code" inputMode="numeric" maxLength="6" required autoFocus autoComplete="one-time-code" />{fieldError('code')}</label>}<button className="button button-primary" type="submit">Continuer</button><button className="auth-logout" type="button" onClick={() => setUseRecovery((value) => !value)}>Utiliser {useRecovery ? 'un code d’authentification' : 'un code de récupération'}</button></form>;
    else form = <form method="POST" action={route.confirmPasswordStore} className="auth-form">{csrf}<label>Mot de passe<input name="password" type="password" required autoFocus autoComplete="current-password" />{fieldError('password')}</label><button className="button button-primary" type="submit">Confirmer</button></form>;
    return <main className="auth-screen"><a className="auth-brand" href={route.home}><span className="brand-leaf"><Icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Information de prix</small></span></a><section className="auth-card"><aside className="auth-photo-panel"><span className="auth-photo-badge"><Icon name="map-pin" /> Marchés du Bénin</span><div className="auth-photo-copy"><span>UNE PLATEFORME POUR NOS FILIÈRES</span><h2>Des prix clairs.<br /><em>Des échanges justes.</em></h2><p>Rapprocher producteurs, commerçants et acheteurs grâce à une information utile.</p></div><small className="auth-photo-caption">Produits locaux · Informations de marché</small></aside><div className="auth-form-panel"><div className="eyebrow">Espace sécurisé · Bénin</div><h1>{heading}</h1><p className="auth-description">{description}</p>{data.statusMessage && <div className="flash-success">{data.statusMessage === 'verification-link-sent' ? 'Un nouveau lien de vérification a été envoyé.' : data.statusMessage}</div>}{Object.keys(data.errors || {}).length > 0 && <div className="flash-error">Certains champs doivent être vérifiés.</div>}{form}</div></section><a className="auth-back" href={route.home}><Icon name="arrow-left" /> Retour à la plateforme</a></main>;
}

function App() {
    const { page, props } = config;
    useEffect(() => {
        const updateProgress = () => {
            const scrollable = document.documentElement.scrollHeight - window.innerHeight;
            const progress = scrollable > 0 ? window.scrollY / scrollable : 0;
            document.documentElement.style.setProperty('--page-progress', String(Math.min(1, Math.max(0, progress))));
        };
        updateProgress();
        window.addEventListener('scroll', updateProgress, { passive: true });
        window.addEventListener('resize', updateProgress);
        return () => {
            window.removeEventListener('scroll', updateProgress);
            window.removeEventListener('resize', updateProgress);
        };
    }, []);
    useEffect(() => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const revealTargets = document.querySelectorAll('.app-main .section-heading, .app-main .home-stats article, .app-main .home-chapter, .app-main .method-step, .app-main .context-points > div, .app-main .home-discover > a, .app-main .price-card, .app-main .directory-card, .app-main .about-grid article, .app-main .empty-state, .app-main .admin-section, .app-main .request-card');
        if (!reduceMotion && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -28px 0px' });
            revealTargets.forEach((element, index) => {
                element.classList.add('scroll-reveal');
                element.style.setProperty('--reveal-delay', `${(index % 4) * 65}ms`);
                observer.observe(element);
            });
            const revealPassedSections = () => revealTargets.forEach((element) => {
                if (element.getBoundingClientRect().top < window.innerHeight + 120) element.classList.add('is-visible');
            });
            window.addEventListener('scroll', revealPassedSections, { passive: true });
            const hero = document.querySelector('.market-hero, .about-hero');
            let frame = 0;
            const updateParallax = () => {
                if (frame) return;
                frame = window.requestAnimationFrame(() => {
                    frame = 0;
                    const bounds = hero?.getBoundingClientRect();
                    if (hero && bounds && bounds.bottom > 0 && bounds.top < window.innerHeight) {
                        hero.style.setProperty('--hero-parallax', `${Math.max(-24, Math.min(24, (window.innerHeight - bounds.top) * 0.035 - 12))}px`);
                    }
                });
            };
            window.addEventListener('scroll', updateParallax, { passive: true });
            updateParallax();
            return () => { observer.disconnect(); window.removeEventListener('scroll', updateParallax); window.removeEventListener('scroll', revealPassedSections); if (frame) window.cancelAnimationFrame(frame); };
        }
        revealTargets.forEach((element) => element.classList.add('is-visible'));
        return undefined;
    }, []);
    let content;
    if (page === 'home') content = <HomePage data={props} />;
    else if (page === 'prices') content = <PricesPage data={props} />;
    else if (page === 'markets' || page === 'products') content = <DirectoryPage data={props} type={page} />;
    else if (page === 'about') content = <AboutPage data={props} />;
    else if (page === 'detail') content = <DetailPage data={props} />;
    else if (page === 'requests') content = <RequestsPage data={props} />;
    else if (page === 'admin') content = <AdminPage data={props} />;
    else if (page.startsWith('auth-')) return <AuthPage page={page} data={props} />;
    else content = <EmptyState title="Page introuvable" text="Cette page n’existe pas." />;

    if (page === 'admin') return <><AdminPage data={props} /></>;
    return <><div className="site-scroll-progress" aria-hidden="true" /><Sidebar /><Topbar /><main className={`app-main${page === 'home' ? ' home-main' : ''}`}>{content}<Footer /></main></>;
}

createRoot(document.getElementById('marketplace-root')).render(<App />);


