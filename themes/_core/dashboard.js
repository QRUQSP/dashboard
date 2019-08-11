var refreshTimer = null;
var resetTimer = null;
var slideshowTimer = null;
var curPanel = null;
function db_ge(p,i) {
    return document.getElementById('panel-' + p['id'] + '-' + i);
}
function db_setInnerHtml(p,i,h) {
    var e = db_ge(p,i);
    if( e != null ) {
        e.textContent = h;
    }
}
function db_update() {
    clearTimeout(refreshTimer);
    // Call the updater for the first panel
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
        document.getElementById('panel-' + lastPanel).style.display = 'none';
        document.getElementById('panel-' + curPanel).style.display = 'block';
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
        document.getElementById('panel-' + lastPanel).style.display = 'none';
        document.getElementById('panel-' + curPanel).style.display = 'block';
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
function db_init() {
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
        if( db_settings['slideshow-mode'] == null || db_settings['slideshow-mode'] != 'auto' 
            || db_settings['slideshow-delay-seconds'] == null || db_settings['slideshow-delay-seconds'] > 15 
            ) {
            refreshTimer = setTimeout(db_update, 10005);
        }
    }
}
window.onload = db_init();
