//
// This is the main app for the dashboard module
//
function qruqsp_dashboard_main() {
    //
    // The panel to list the dashboard
    //
    this.menu = new M.panel('dashboard', 'qruqsp_dashboard_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.dashboard.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search dashboard',
            'noData':'No dashboard found',
            },
        'dashboards':{'label':'Dashboards', 'type':'simplegrid', 'num_cols':1,
            'noData':'No dashboard',
            'addTxt':'Add Dashboards',
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
            }
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
        'panels':{'label':'Panels', 'type':'simplegrid', 'num_cols':1,
            'noData':'No panels added',
            'addTxt':'Add Panel',
            'addFn':'M.qruqsp_dashboard_main.panel.open(\'M.qruqsp_dashboard_main.dashboard.open();\',0,null);'
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
        return 'M.qruqsp_dashboard_main.panel.open(\'M.qruqsp_dashboard_main.dashboard.open();\',' + d.id + ',null);';
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
    // The panel to edit Panel
    //
    this.panel = new M.panel('Panel', 'qruqsp_dashboard_main', 'panel', 'mc', 'medium', 'sectioned', 'qruqsp.dashboard.main.panel');
    this.panel.data = null;
    this.panel.panels = null;
    this.panel.panel_id = 0;
    this.panel.nplist = [];
    this.panel.sections = {
        'general':{'label':'', 'fields':{
            'panel_title':{'label':'Title', 'required':'yes', 'type':'text'},
            'panel_sequence':{'label':'Order', 'type':'text'},
            }},
        '_panel':{'label':'Choose the panel template', 'fields':{
            'panel_ref':{'label':'', 'hidelabel':'yes', 'required':'yes', 'type':'select', 
                'options':{}, //'complex_options':{'value':'value', 'name':'name'},
                'onchange':'M.qruqsp_dashboard_main.panel.updateModuleOptions();',
                },
            }},
        '_panel_options':{'label':'Options', 'visible':'hidden', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_dashboard_main.panel.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_dashboard_main.panel.panel_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_dashboard_main.panel.remove();'},
            }},
        };
    this.panel.fieldValue = function(s, i, d) { 
        if( s == '_panel_options' ) {
            return this.data.settings[i];
        }
        return this.data[i]; 
    }
    this.panel.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.dashboard.panelHistory', 'args':{'tnid':M.curTenantID, 'panel_id':this.panel_id, 'field':i}};
    }
    this.panel.updateModuleOptions = function() {
        console.log('set options');
        this.setModuleOptions(this.formValue('panel_ref'));
    }
    this.panel.setModuleOptions = function(option) {
        this.sections._panel_options.fields = {};
        if( this.panels[option] != null && this.panels[option].options != null ) {
            this.sections._panel_options.fields = this.panels[option].options;
            this.sections._panel_options.visible = 'yes';
        } else {
            this.sections._panel_options.visible = 'hidden';
        }
        this.refreshSection('_panel_options');
    }
    this.panel.open = function(cb, pid, list) {
        if( pid != null ) { this.panel_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.dashboard.panelGet', {'tnid':M.curTenantID, 'panel_id':this.panel_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_dashboard_main.panel;
            p.data = rsp.panel;
            p.sections._panel.fields.panel_ref.options = [];
            for(var i in rsp.panels) {
                p.sections._panel.fields.panel_ref.options[rsp.panels[i].value] = rsp.panels[i].name;
            }
            p.panels = rsp.panels;
            p.setModuleOptions(p.data.panel_ref);
            p.refresh();
            p.show(cb);
        });
    }
    this.panel.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_dashboard_main.panel.close();'; }
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
                M.qruqsp_dashboard_main.panel.panel_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.panel.remove = function() {
        if( confirm('Are you sure you want to remove panel?') ) {
            M.api.getJSONCb('qruqsp.dashboard.panelDelete', {'tnid':M.curTenantID, 'panel_id':this.panel_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_dashboard_main.panel.close();
            });
        }
    }
    this.panel.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.panel_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_dashboard_main.panel.save(\'M.qruqsp_dashboard_main.panel.open(null,' + this.nplist[this.nplist.indexOf('' + this.panel_id) + 1] + ');\');';
        }
        return null;
    }
    this.panel.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.panel_id) > 0 ) {
            return 'M.qruqsp_dashboard_main.panel.save(\'M.qruqsp_dashboard_main.panel.open(null,' + this.nplist[this.nplist.indexOf('' + this.panel_id) - 1] + ');\');';
        }
        return null;
    }
    this.panel.addButton('save', 'Save', 'M.qruqsp_dashboard_main.panel.save();');
    this.panel.addClose('Cancel');
    this.panel.addButton('next', 'Next');
    this.panel.addLeftButton('prev', 'Prev');

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
