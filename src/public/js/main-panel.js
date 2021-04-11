var mainpanel = Vue.component('main-panel', {
    data: function() {
        return {
        }
    },
    
    props: ['config'],
    
    methods: {
    	saveWorkspace: function() {
            let returnData = { ...this.config.Workspaces[this.config.DefaultWorkspace]};
            // Remove fixed data values
            delete returnData.dataSources;
            delete returnData.dataTypes;
           
            // Check if data changed
            let newJSON = JSON.stringify(returnData);
            if ( newJSON != localStorage.getItem('saveJSON') )   {
               $.ajax({
                    url: "/save",   // ?XDEBUG_SESSION=1
                    headers: {"Authorization": "Bearer " + Cookies.get('JWT') },
                    type: "post",
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    data: newJSON
                });
                localStorage.setItem('saveJSON', newJSON);
            }
        },
    },
    
    render(createElement) {
        let workspace = this.config.Workspaces[this.config.DefaultWorkspace];
        workspace.dataTypes = this.config.StatsType;
        return createElement(
          'pane', {
              attrs: {
                  	workspace: workspace,
                  	config: workspace.Config,
                  	panels: this.config.Panels,
                  	basepanel: true,
                    // TODO make dynamic 
					panes: workspace.Windows[0]['children']
              },
              on: {
            	  'save-workspace': function(data) {
                      this.saveWorkspace(data);
                  }.bind(this)
              }
          }
        );
    }
})
