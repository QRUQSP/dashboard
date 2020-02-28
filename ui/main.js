//
// This is the main app for the dashboard module
//
function qruqsp_dashboard_main() {
    //
    // The panel to list the dashboard
    //
    this.menu = new M.panel('Dashboard', 'qruqsp_dashboard_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.dashboard.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search dashboard',
//            'noData':'No dashboard found',
//            },
        'dashboards':{'label':'Dashboards', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['', 'alignright'],
            'noData':'No dashboard',
            'addTxt':'Add Dashboard',
            'addFn':'M.qruqsp_dashboard_main.dashboard.open(\'M.qruqsp_dashboard_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.dashboard.dashboardSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_dashboard_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_dashboard_main.menu.panelUID + '_' + s), rsp.dashboards);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_dashboard_main.dashboard.open(\'M.qruqsp_dashboard_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'dashboards' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return '<a class="website" target="_blank" onclick="event.stopPropagation(); return true;" href="' + d.url + '">' + d.url + '</a>';
            }
        }
    }
    this.menu.cellFn = function(s, i, j, d) {
        if( s == 'dashboards' && j == 1 ) {
//            return 'event.stopPropagation();';
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'dashboards' ) {
            return 'M.qruqsp_dashboard_main.dashboard.open(\'M.qruqsp_dashboard_main.menu.open();\',\'' + d.id + '\',M.qruqsp_dashboard_main.dashboard.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.dashboard.dashboardList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Dashboards
    //
    this.dashboard = new M.panel('Dashboards', 'qruqsp_dashboard_main', 'dashboard', 'mc', 'medium', 'sectioned', 'qruqsp.dashboard.main.dashboard');
    this.dashboard.data = null;
    this.dashboard.dashboard_id = 0;
    this.dashboard.nplist = [];
    this.dashboard.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
//            'theme':{'label':'Theme', 'type':'text'},
//            'password':{'label':'Password', 'type':'text'},
            }},
        'slideshow':{'label':'Panel Advance',
            'visible':function() { return M.qruqsp_dashboard_main.dashboard.data.panels != null && M.qruqsp_dashboard_main.dashboard.data.panels.length > 1 ? 'yes' : 'no'; },
            'fields':{
                'slideshow-mode':{'label':'Advance Mode', 'type':'select', 
                    'options':{'auto':'Automatic', 'manual':'Manual'},
                    'onchange':'M.qruqsp_dashboard_main.dashboard.updateSlideshow();',
                    },
                'slideshow-delay-seconds':{'label':'Panel Display Time', 'required':'no', 'type':'select', 
                    'visible':'no',
                    'options':{
                        '5':'5 Seconds',
                        '10':'10 Seconds',
                        '15':'15 Seconds',
                        '20':'20 Seconds',
                        '30':'30 Seconds',
                        '60':'1 Minute',
                        '120':'2 Minutes',
                        '300':'5 Minutes',
                        '600':'10 Minutes',
                        '900':'15 Minutes',
                        '1200':'20 Minutes',
                        '1800':'30 Minutes',
                    }},
                'slideshow-reset-seconds':{'label':'Reset to 1st panel after', 'required':'no', 'type':'select', 
                    'visible':'no',
                    'options':{
                        '0':'No Reset',
                        '15':'15 Seconds',
                        '20':'20 Seconds',
                        '30':'30 Seconds',
                        '60':'1 Minute',
                        '300':'5 Minutes',
                        '600':'10 Minutes',
                        '900':'15 Minutes',
                        '1200':'20 Minutes',
                        '1800':'30 Minutes',
                    }},
            }},
        'panels':{'label':'Panels', 'type':'simplegrid', 'num_cols':1,
            'noData':'No panels added',
            'addTxt':'Add Panel',
            'addFn':'M.qruqsp_dashboard_main.dashboard.save("M.qruqsp_dashboard_main.paneledit.open(\'M.qruqsp_dashboard_main.dashboard.open();\',0,M.qruqsp_dashboard_main.dashboard.dashboard_id,null);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_dashboard_main.dashboard.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_dashboard_main.dashboard.dashboard_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_dashboard_main.dashboard.remove();'},
            }},
        };
    this.dashboard.fieldValue = function(s, i, d) { return this.data[i]; }
    this.dashboard.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.dashboard.dashboardHistory', 'args':{'tnid':M.curTenantID, 'dashboard_id':this.dashboard_id, 'field':i}};
    }
    this.dashboard.cellValue = function(s, i, j, d) {
        if( s == 'panels' ) {
            switch(j) {
                case 0: return d.title;
            }
        }
    }
    this.dashboard.rowFn = function(s, i, d) {
        return 'M.qruqsp_dashboard_main.panel.open(\'M.qruqsp_dashboard_main.dashboard.open();\',' + d.id + ',M.qruqsp_dashboard_main.dashboard.dashboard_id,null);';
    }
    this.dashboard.updateSlideshow = function() {
        if( this.formValue('slideshow-mode') == 'manual' ) {
            this.sections.slideshow.fields['slideshow-delay-seconds'].visible = 'no';
            this.sections.slideshow.fields['slideshow-reset-seconds'].visible = 'yes';
        } else {
            this.sections.slideshow.fields['slideshow-delay-seconds'].visible = 'yes';
            this.sections.slideshow.fields['slideshow-reset-seconds'].visible = 'no';
        }
        this.showHideFormField('slideshow', 'slideshow-delay-seconds');
        this.showHideFormField('slideshow', 'slideshow-reset-seconds');
    }
    this.dashboard.open = function(cb, did, list) {
        if( did != null ) { this.dashboard_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.dashboard.dashboardGet', {'tnid':M.curTenantID, 'dashboard_id':this.dashboard_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.dashboard;
            p.data = rsp.dashboard;
            if( rsp.dashboard['slideshow-mode'] != null && rsp.dashboard['slideshow-mode'] == 'manual' ) {
                p.sections.slideshow.fields['slideshow-delay-seconds'].visible = 'no';
                p.sections.slideshow.fields['slideshow-reset-seconds'].visible = 'yes';
            } else {
                p.sections.slideshow.fields['slideshow-delay-seconds'].visible = 'yes';
                p.sections.slideshow.fields['slideshow-reset-seconds'].visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.dashboard.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_dashboard_main.dashboard.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.dashboard_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.dashboard.dashboardUpdate', {'tnid':M.curTenantID, 'dashboard_id':this.dashboard_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.dashboard.dashboardAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.dashboard.dashboard_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.dashboard.remove = function() {
        if( confirm('Are you sure you want to remove dashboard?') ) {
            M.api.getJSONCb('qruqsp.dashboard.dashboardDelete', {'tnid':M.curTenantID, 'dashboard_id':this.dashboard_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.dashboard.close();
            });
        }
    }
    this.dashboard.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.dashboard_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_dashboard_main.dashboard.save(\'M.qruqsp_dashboard_main.dashboard.open(null,' + this.nplist[this.nplist.indexOf('' + this.dashboard_id) + 1] + ');\');';
        }
        return null;
    }
    this.dashboard.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.dashboard_id) > 0 ) {
            return 'M.qruqsp_dashboard_main.dashboard.save(\'M.qruqsp_dashboard_main.dashboard.open(null,' + this.nplist[this.nplist.indexOf('' + this.dashboard_id) - 1] + ');\');';
        }
        return null;
    }
    this.dashboard.addButton('save', 'Save', 'M.qruqsp_dashboard_main.dashboard.save();');
    this.dashboard.addClose('Cancel');
    this.dashboard.addButton('next', 'Next');
    this.dashboard.addLeftButton('prev', 'Prev');

    //
    // The panel view
    //
    this.panel = new M.panel('Panel', 'qruqsp_dashboard_main', 'panel', 'mc', 'large', 'sectioned', 'qruqsp.dashboard.main.panel');
    this.panel.data = null;
    this.panel.panel_id = 0;
    this.refreshTimer = null;
    this.panel.sections = {
        'html':{'label':'', 'hidelabel':'yes', 'type':'htmlcontent'},
        };
    this.panel.sectionData = function(s) {
        if( s == 'html' ) {
            return this.data[s];
        }
    }
    this.panel.open = function(cb, pid, did, list) {
        if( pid != null ) { this.panel_id = pid; }
        if( did != null ) { this.dashboard_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.dashboard.panelGet', {'tnid':M.curTenantID, 'panel_id':this.panel_id, 'generate':'editui'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.panel;
            p.data = rsp;
            console.log(rsp);
            this.dashboard_id = p.data.panel.dashboard_id;
            var css = '';
            if( rsp.panel != null && rsp.panel.cells != null ) {
                for(var i in rsp.panel.cells) {
                    if( rsp.panel.cells[i].css != '' ) {
                        css += rsp.panel.cells[i].css;
                    }
                }
                if( css != '' ) {
                    var s = M.gE(p.panelUID + '_cells_css');
                    if( s == null ) {
                        s = document.createElement('style');
                        s.setAttribute('id', p.panelUID + '_cells_css');
                        document.head.appendChild(s);
                    }
                    s.innerHTML = css;
                }
            }

            p.refresh();
            p.show(cb);
            p.resizeCells();
        });
    }
    this.panel.editCell = function(cid) {
        M.qruqsp_dashboard_main.cell.open('M.qruqsp_dashboard_main.panel.open();', cid);
    }
    this.panel.addCell = function(x,y) {
        M.qruqsp_dashboard_main.cell.open('M.qruqsp_dashboard_main.panel.open();', 0, this.panel_id, null, x, y);
    }
    this.panel.dragStart = function(event, cid) {
        event.dataTransfer.setData("cell_id", cid);
    }
    this.panel.dropCell = function(event,x,y) {
        M.api.getJSONCb('qruqsp.dashboard.cellUpdate', {'tnid':M.curTenantID, 'cell_id':event.dataTransfer.getData("cell_id"), 'row':x, 'col':y}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.qruqsp_dashboard_main.panel.open();
        });
    }
    this.panel.resizeCells = function() {
        var s = M.gE(this.panelUID + '_sizing_css');
        if( s == null ) {
            s = document.createElement('style');
            s.setAttribute('id', this.panelUID + '_sizing_css');
            document.head.appendChild(s);
        } else {
            s.innerHTML = '';
        }
        var h = M.gE('dbpanel-' + this.panel_id).offsetHeight-2;
        var w = M.gE('dbpanel-' + this.panel_id).offsetWidth-8-(this.data.panel.numcols*2);
        var h = (w/this.data.panel.numcols) * this.data.panel.numrows;
        s.innerHTML += "#" + this.panelUID + "_section_html table.border > tbody > tr > td { background: #000; }";
        s.innerHTML += "table.dbpanel-" + this.panel_id + " {"
            + "border-collapse: collapse; "
            + "box-sizing: border-box; "
            + "background: #000;"
            + "width: " + w + "px; "
            + "} "
        s.innerHTML += "table.dbpanel-" + this.panel_id + " div, "
            + "table.dbpanel-" + this.panel_id + " span, "
            + "table.dbpanel-" + this.panel_id + " table, "
            + "table.dbpanel-" + this.panel_id + " tbody, "
            + "table.dbpanel-" + this.panel_id + " tr, "
            + "table.dbpanel-" + this.panel_id + " th, "
            + "table.dbpanel-" + this.panel_id + " td, "
            + "table.dbpanel-" + this.panel_id + " div {"
            + "box-sizing: border-box; "
            + "}; ";
        s.innerHTML += "table.dbpanel { width: " + w + "px;}";
        s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.over { "
            + "background: #ccc; "
            + "}";
        s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td { "
            + "width: " + Math.round(w/this.data.panel.numcols) + "px; "
            + "height: " + Math.round(h/this.data.panel.numrows) + "px; "
            + "border: 1px solid yellow;"
            + "overflow: hidden;"
            + "}";
        s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td svg, "
            + "table.dbpanel-" + this.panel_id + " > tbody > tr > td div.empty { "
                + "width: " + Math.round(w/this.data.panel.numcols) + "px; "
                + "height: " + Math.round(h/this.data.panel.numrows) + "px; "
            + "}";
        for(var j = 2; j <= this.data.panel.numcols; j++) {
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.w" + j + " {width: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.w" + j + " .widget {width: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.w" + j + " svg {width: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
        }
        for(var j = 2; j <= this.data.panel.numrows; j++) {
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.h" + j + " {height: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.h" + j + " .widget {height: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
            s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr > td.h" + j + " svg {height: " + Math.round((w/this.data.panel.numcols)*j) + "px;}";
        }
        s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr.spacing > td {"
            + "height: 1px !important; "
            + "border-left: 0px solid #000; "
            + "border-top: 0px solid #000; "
            + "border-right: 0px solid #000; "
            + "} "
        s.innerHTML += "table.dbpanel-" + this.panel_id + " > tbody > tr.spacing > td:first-child, "
            + "table.dbpanel-" + this.panel_id + " tr td.spacing {"
            + "width: 1px !important; "
            + "border-left: 0px solid #000; "
            + "border-top: 0px solid #000; "
            + "border-bottom: 0px solid #000; "
            + "} "
    }
    this.panel.addButton('edit', 'Edit', 'M.qruqsp_dashboard_main.paneledit.open("M.qruqsp_dashboard_main.panel.open();",M.qruqsp_dashboard_main.panel.panel_id);');
    this.panel.addClose('Back');

    //
    // The panel to edit Panel
    //
    this.paneledit = new M.panel('Panel', 'qruqsp_dashboard_main', 'paneledit', 'mc', 'medium', 'sectioned', 'qruqsp.dashboard.main.paneledit');
    this.paneledit.data = null;
    this.paneledit.panels = null;
    this.paneledit.panel_id = 0;
    this.paneledit.dashboard_id = 0;
    this.paneledit.nplist = [];
    this.paneledit.sections = {
        'general':{'label':'', 'fields':{
            'title':{'label':'Title', 'required':'yes', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text'},
            'numcols':{'label':'Grid Columns', 'type':'text', 'size':'small'},
            'numrows':{'label':'Grid Rows', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_dashboard_main.paneledit.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_dashboard_main.paneledit.panel_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_dashboard_main.paneledit.remove();'},
            }},
        };
    this.paneledit.fieldValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.paneledit.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.dashboard.panelHistory', 'args':{'tnid':M.curTenantID, 'panel_id':this.panel_id, 'field':i}};
    }
    this.paneledit.cellValue = function(s, i, j, d) {
        if( s == 'cells' ) {
            switch(j) {
                case 0: return d.row;
                case 1: return d.col;
                case 2: return d.name;
            }
        }
    }
    this.paneledit.rowFn = function(s, i, d) {
        if( s == 'cells' ) {
            return 'M.qruqsp_dashboard_main.cell.open(\'M.qruqsp_dashboard_main.paneledit.open();\',\'' + d.id + '\',0,M.qruqsp_dashboard_main.paneledit.nplist);';
        }
    }
    this.paneledit.open = function(cb, pid, did, list) {
        if( pid != null ) { this.panel_id = pid; }
        if( did != null ) { this.dashboard_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.dashboard.panelGet', {'tnid':M.curTenantID, 'panel_id':this.panel_id, 'dashboard_id':this.dashboard_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.paneledit;
            p.data = rsp.panel;
            p.refresh();
            p.show(cb);
        });
    }
    this.paneledit.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_dashboard_main.paneledit.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.panel_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.dashboard.panelUpdate', {'tnid':M.curTenantID, 'panel_id':this.panel_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.dashboard.panelAdd', {'tnid':M.curTenantID, 'dashboard_id':this.dashboard_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.paneledit.panel_id = rsp.id;
                M.qruqsp_dashboard_main.panel.open(this.cb, rsp.id, this.dashboard_id, null);
            });
        }
    }
    this.paneledit.remove = function() {
        if( confirm('Are you sure you want to remove panel?') ) {
            M.api.getJSONCb('qruqsp.dashboard.panelDelete', {'tnid':M.curTenantID, 'panel_id':this.panel_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.paneledit.close();
            });
        }
    }
    this.paneledit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.panel_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_dashboard_main.paneledit.save(\'M.qruqsp_dashboard_main.paneledit.open(null,' + this.nplist[this.nplist.indexOf('' + this.panel_id) + 1] + ');\');';
        }
        return null;
    }
    this.paneledit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.panel_id) > 0 ) {
            return 'M.qruqsp_dashboard_main.paneledit.save(\'M.qruqsp_dashboard_main.paneledit.open(null,' + this.nplist[this.nplist.indexOf('' + this.panel_id) - 1] + ');\');';
        }
        return null;
    }
    this.paneledit.addButton('save', 'Save', 'M.qruqsp_dashboard_main.paneledit.save();');
    this.paneledit.addClose('Cancel');
    this.paneledit.addButton('next', 'Next');
    this.paneledit.addLeftButton('prev', 'Prev');

    //
    // The panel to edit cell
    //
    this.cell = new M.panel('Widget', 'qruqsp_dashboard_main', 'cell', 'mc', 'large', 'sectioned', 'qruqsp.dashboard.main.cell');
    this.cell.data = null;
    this.cell.widgets = null;
    this.cell.cell_id = 0;
    this.cell.panel_id = 0;
    this.cell.nplist = [];
    this.cell.sections = {
        '_widget':{'label':'Choose the widget', 'fields':{
            'widget_ref':{'label':'', 'hidelabel':'yes', 'required':'yes', 'type':'select', 
                'options':{}, //'complex_options':{'value':'value', 'name':'name'},
                'onchange':'M.qruqsp_dashboard_main.cell.updateOptions();',
                },
            }},
        'position':{'label':'Position', 'visible':'hidden', 'fields':{
            'row':{'label':'Row', 'type':'text', 'size':'small'},
            'col':{'label':'Column', 'type':'text', 'size':'small'},
            }},
        'size':{'label':'Size', 'fields':{
            'rowspan':{'label':'Rows', 'type':'text', 'size':'small'},
            'colspan':{'label':'Columns', 'type':'text', 'size':'small'},
            }},
        '_options':{'label':'Options', 'visible':'hidden', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_dashboard_main.cell.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_dashboard_main.cell.cell_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_dashboard_main.cell.remove();'},
            }},
        };
    this.cell.fieldValue = function(s, i, d) { 
        if( s == '_options' ) {
            return this.data.settings[i];
        }
        return this.data[i]; 
    }
    this.cell.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.dashboard.cellHistory', 'args':{'tnid':M.curTenantID, 'cell_id':this.cell_id, 'field':i}};
    }
    this.cell.updateOptions = function() {
        this.setModuleOptions(this.formValue('widget_ref'));
    }
    this.cell.setModuleOptions = function(option) {
        this.sections._options.fields = {};
        if( this.widgets[option] != null && this.widgets[option].options != null ) {
            this.sections._options.visible = 'yes';
            this.sections._options.fields = this.widgets[option].options;
        } else {
            this.sections._options.visible = 'hidden';
        }
        this.refreshSection('_options');
    }
    this.cell.refreshFields = function(fields) {
        for(var i in fields) {
            this.showHideFormField('_options', fields[i]);
        }
    }
    this.cell.open = function(cb, cid, pid, list, x, y) {
        if( cid != null ) { this.cell_id = cid; }
        if( pid != null ) { this.panel_id = pid; }
        if( list != null ) { this.nplist = list; }
        var args = {'tnid':M.curTenantID, 'cell_id':this.cell_id, 'panel_id':this.panel_id};
        if( x != null ) { args['row'] = x; }
        if( y != null ) { args['col'] = y; }
        M.api.getJSONCb('qruqsp.dashboard.cellGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.cell;
            p.data = rsp.cell;
            p.sections._widget.fields.widget_ref.options = [];
            for(var i in rsp.widgets) {
                p.sections._widget.fields.widget_ref.options[i] = rsp.widgets[i].category + ' - ' + rsp.widgets[i].name;
/*                for(var j in rsp.widgets[i].options) {
                    if( rsp.widgets[i].options[j].vfield != null && rsp.widgets[i].options[j].vshow != null ) {
                        rsp.widgets[i].options[j].visible = function() {
                            var p = M.qruqsp_dashboard_main.panel;
                            var v = p.formValue(this.vfield);
                            if( v == null && p.data.settings[this.vfield] != null ) {
                                v = p.data.settings[this.vfield];
                            }
                            if( v == null && this.vdefault != null ) {
                                return this.vdefault;
                            }
                            if( this.vshow.includes(v) ) {
                                return 'yes';
                            }
                            return 'no';
                        };
                    }
                } */
            }
            p.widgets = rsp.widgets;
            p.refresh();
            p.show(cb);
            p.setModuleOptions(p.data.widget_ref);
        });
    }
    this.cell.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_dashboard_main.cell.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.cell_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.dashboard.cellUpdate', {'tnid':M.curTenantID, 'cell_id':this.cell_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.dashboard.cellAdd', {'tnid':M.curTenantID, 'panel_id':this.panel_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.cell.cell_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.cell.remove = function() {
        if( confirm('Are you sure you want to remove the widget?') ) {
            M.api.getJSONCb('qruqsp.dashboard.cellDelete', {'tnid':M.curTenantID, 'cell_id':this.cell_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.cell.close();
            });
        }
    }
    this.cell.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.cell_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_dashboard_main.cell.save(\'M.qruqsp_dashboard_main.cell.open(null,' + this.nplist[this.nplist.indexOf('' + this.cell_id) + 1] + ');\');';
        }
        return null;
    }
    this.cell.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.cell_id) > 0 ) {
            return 'M.qruqsp_dashboard_main.cell.save(\'M.qruqsp_dashboard_main.cell.open(null,' + this.nplist[this.nplist.indexOf('' + this.cell_id) - 1] + ');\');';
        }
        return null;
    }
    this.cell.addButton('save', 'Save', 'M.qruqsp_dashboard_main.cell.save();');
    this.cell.addClose('Cancel');
    this.cell.addButton('next', 'Next');
    this.cell.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_dashboard_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
