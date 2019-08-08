var refreshTimer = null;
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
    refreshTimer = setTimeout(db_update, 5000);
}
function db_init() {
    // Initialize the panels
    for(var i in db_panels) {
        if( db_panels[i].init != null && typeof(db_panels[i].init) == 'function' ) {
            db_panels[i].init(db_panels[i]);
        }
    }
    // Setup the first panel
    if( typeof db_panel_order !== 'undefined' && db_panel_order[0] != null ) {
        curPanel = db_panel_order[0];
        refreshTimer = setTimeout(db_update, 5000);
    }
}
window.onload = db_init();
