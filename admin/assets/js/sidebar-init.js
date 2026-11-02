// Runs in <head> so a collapsed menu does not flash open on every page.
try { if (window.innerWidth > 768 && localStorage.getItem('bunSidebar') === 'collapsed') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
