var refreshTimer = null;
var resetTimer = null;
var slideshowTimer = null;
var curPanel = null;
function db_ge(p,i) {
    return document.getElementById('widget-' + p.id + (i!=''?'-':'') + i);
}
function db_setInnerHtml(p,i,h) {
    var e = db_ge(p,i);
    if( e != null ) {
        e.textContent = '' + h;
    }
}
function db_update() {
    clearTimeout(refreshTimer);
    // Call the updater for the current panel
    if( curPanel != null && db_panels[curPanel].update != null && typeof(db_panels[curPanel].update) == 'function' ) {
        var c = '';
        if( db_panels[curPanel].update_args != null && typeof(db_panels[curPanel].update_args) == 'function' ) {
            c = db_panels[curPanel].update_args();
        }
        var x = new XMLHttpRequest();
        x.open('POST',url+'?update=' + curPanel,true);
        x.onreadystatechange = function() {
            if( x.readyState == 4 && x.status == 200 ) {
                var rsp = eval('('+x.responseText+')');
                if( rsp != null && rsp.data != null && rsp.data[curPanel] != null ) {
                    db_panels[curPanel].update(rsp.data[curPanel]);
                }
                if( rsp.lastupdated != null ) {
                    document.getElementById('lastupdated').innerHTML = rsp.lastupdated;
                }
            }
            if( x.readyState > 2 && x.status >= 300 ) {
                // FIXME: Need a warning symbol when no data for certain amount of time
                console.log('Unable to get update');
            }
        }
        x.send(null);
    }
    // If custom updater has been specified
    else if( curPanel != null && db_panels[curPanel].updater != null && typeof(db_panels[curPanel].updater) == 'function' ) {
        db_panels[curPanel].updater();
    }
    if( db_settings['slideshow-mode'] == null || db_settings['slideshow-mode'] != 'auto' 
        || db_settings['slideshow-delay-seconds'] == null || db_settings['slideshow-delay-seconds'] > 15 
        ) {
        refreshTimer = setTimeout(db_update, 10005);
    }
}
function db_reset() {
    clearTimeout(resetTimer);
    clearTimeout(slideshowTimer);
    var lastPanel = curPanel;
    if( curPanel != db_panel_order[0] ) {
        curPanel = db_panel_order[0];
        db_update();
        document.getElementById('dbpanel-' + lastPanel).style.display = 'none';
        document.getElementById('dbpanel-' + curPanel).style.display = 'block';
    }
}
function db_advance() { 
    clearTimeout(resetTimer);
    clearTimeout(slideshowTimer);
    var lastPanel = curPanel;
    var i = db_panel_order.indexOf(curPanel);
    i++;
    if( i < db_panel_order.length ) {
        curPanel = db_panel_order[i];
    } else if( i > 0 ) {
        curPanel = db_panel_order[0];
    }
    if( lastPanel != curPanel ) {
        db_update();
        document.getElementById('dbpanel-' + lastPanel).style.display = 'none';
        document.getElementById('dbpanel-' + curPanel).style.display = 'block';
        if( db_settings['slideshow-mode'] != null && db_settings['slideshow-mode'] == 'manual' ) {
            if( db_settings['slideshow-reset-seconds'] != null && db_settings['slideshow-reset-seconds'] > 0 ) {
                resetTimer = setTimeout(db_reset, (db_settings['slideshow-reset-seconds']*1000));
            } else {
                resetTimer = setTimeout(db_reset, 60000);
            }
        }
    }
    if( db_settings['slideshow-mode'] != null && db_settings['slideshow-mode'] == 'auto' ) {
        if( db_settings['slideshow-delay-seconds'] != null && db_settings['slideshow-delay-seconds'] > 0 ) {
            slideshowTimer = setTimeout(db_advance, (db_settings['slideshow-delay-seconds']*1000));
        } else {
            slideshowTimer = setTimeout(db_advance, 60000);
        }
    }
}
function db_resize() {
    var s = document.getElementById('sizing');
    s.innerHTML = '';

    var h = window.innerHeight - 1;
    var w = window.innerWidth - 1;
    s.innerHTML += "table.dbpanel { width: " + w + "px; }";
    s.innerHTML += "table.dbpanel { height: " + h + "px; }";
    for(var i in db_panels) {
        s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td { "
            + "width: " + Math.round(w/db_panels[i].numcols) + "px; "
            + "height: " + Math.round(h/db_panels[i].numrows) + "px; "
            + "}";
        s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td svg { "
            + "width: " + Math.round(w/db_panels[i].numcols) + "px; "
            + "height: " + Math.round(h/db_panels[i].numrows) + "px; "
            + "}";
        for(var j = 2; j <= db_panels[i].numcols; j++) {
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.w" + j + " {width: " + Math.round((w/db_panels[i].numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.w" + j + " .widget {width: " + Math.round((w/db_panels[i].numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.w" + j + " svg {width: " + Math.round((w/db_panels[i].numcols)*j) + "px;}";
        }
        for(var j = 2; j <= db_panels[i].numrows; j++) {
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.h" + j + " {height: " + Math.round((h/db_panels[i].numrows)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.h" + j + " .widget {height: " + Math.round((h/db_panels[i].numrows)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + i + " > tbody > tr > td.h" + j + " svg {height: " + Math.round((h/db_panels[i].numrows)*j) + "px;}";
        }
    }
}
function db_init() {
    setTimeout(db_resize, 500);
    db_resize();
    // Initialize the panels
    for(var i in db_panels) {
        if( db_panels[i].init != null && typeof(db_panels[i].init) == 'function' ) {
            db_panels[i].init(db_panels[i]);
        }
    }
    // Setup the first panel, and initialize slideshow
    if( typeof db_panel_order !== 'undefined' && db_panel_order[0] != null ) {
        curPanel = db_panel_order[0];
        // Setup panel slideshow
        if( db_panel_order.length > 1 ) {
            if( db_settings['slideshow-mode'] != null && db_settings['slideshow-mode'] == 'auto' ) {
                if( db_settings['slideshow-delay-seconds'] != null && db_settings['slideshow-delay-seconds'] > 0 ) {
                    slideshowTimer = setTimeout(db_advance, (db_settings['slideshow-delay-seconds']*1000));
                } else {
                    slideshowTimer = setTimeout(db_advance, 60000);
                }
            }
        }
    }
    if( db_settings['slideshow-mode'] == null || db_settings['slideshow-mode'] != 'auto' 
        || db_settings['slideshow-delay-seconds'] == null || db_settings['slideshow-delay-seconds'] > 15 
        ) {
        refreshTimer = setTimeout(db_update, 53005);
    }
}
window.onload = db_init();
