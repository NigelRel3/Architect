const SIDE_BAR = 1;
const TAB_BAR = 2;
const BOTTOM_BAR = 3;
const NEW_TAB = 4;

var pane = Vue.component('pane', {
    data: function() {
        return {
        	horizontal: false,
            menuUpdate: { version: 0 },
            panelKeys: [],
            panelIDs: [],
            tabIDs: [],
            openToThisPanel: false,
            panelKey: 0,
            activeTab: ''
        }
    },
    
    props: ['workspace', 'config', 'panels', 'basepanel'],
    
    created: function() {
        this.horizontal = this.workspace.Config.horizontal;
        
        this.setPanelSizes();
    }, 
 
    updated: function () {
        if ( this.activeTab != '' )  {
            this.$nextTick(function () {
                $('a[href="#' + this.activeTab + '"]').tab('show');
                this.activeTab = '';
            });
        }
    },

    methods: {
        setPanelSizes: function()   {
            // Need to add all known sizes and then divide remaining up
            // between amount of unknown sizes
            let totalSizeSet = 0;
            let sizeNotSet = [];
            let totalPanes = 0;
            for ( const paneIndex in this.config.panes) {
                let pane = this.config.panes[paneIndex];
                if ( paneIndex === "OpenToThisPanel")   {
                    continue;
                }
                totalPanes++;
                // If pane size set - but not an autosized panel
                if ( pane.size &&  !pane.autoSized )    {
                    totalSizeSet += pane.size;
                }
                else    {
                    sizeNotSet.push(paneIndex);
                }
                this.panelKeys [paneIndex] = 0;
                let id = "pane_" + Math.floor(Math.random() * 100000);
                this.panelIDs.push(id);
                if ( pane.tabs )    {
                    this.tabIDs[paneIndex] = [];
                    for ( const tabIndex in pane.tabs) {
                        this.tabIDs[paneIndex][tabIndex] = id + "_" + tabIndex;
                    }
                }
            }
            // If sizeNotSet is 0, then all elements have size set so result doesn't matter
            if ( sizeNotSet.length != 0 )  {
                let remainSize = (100 - totalSizeSet) / sizeNotSet.length;
                if ( this.config.sizeable ) {
                    remainSize -= (0.5 * (totalPanes-1));
                }
                remainSize = Math.round(remainSize * 100) / 100;
                // Assign sizes to rest of panels
                this.config.panes.forEach ( function(pane, paneIndex) {
                    if ( paneIndex !== "OpenToThisPanel")   {
                        if ( ! pane.size || pane.autoSized == true )    {
                            pane.size = remainSize;
                            pane.autoSized = true;
                        }
                    }
                });

            }
        },
        
    	resizePane: function(e, paneIndex) {
    	    e = e || window.event;
    	    e.preventDefault();
    	    document.onmouseup = 
    		    function () { 
    	    		document.onmouseup = null;
    	    		document.onmousemove = null;
    	    		this.$emit('save-workspace');
    	    	}.bind (this);
    	    document.onmousemove = 
    		    function ( paneIndex, e ) { 
    			    e = e || window.event;
    			    e.preventDefault();
                    
    			    let newSize = 0;
                    let div2Size = 0
                    let div1 = document.getElementById ( this.panelIDs[paneIndex] );
                    let div2 = document.getElementById ( this.panelIDs[paneIndex + 1] );
                    // Find width of panes
                    let div1R = div1.getBoundingClientRect();
                    let div2R = div2.getBoundingClientRect();
    			    if ( this.horizontal ) {
                        if ( e.clientX < div1R.left || e.clientX > div2R.right )  {
                            return;
                        }
                        let scale =  window.innerWidth/100;
                        newSize = (e.clientX - div1R.left) / scale;
                        let change = this.config.panes[paneIndex].size - newSize;

                        // As pane has been sized manually, remove autosize flag
                        this.config.panes[paneIndex].autoSized = false;
    				    div1.style.width = newSize + "%";
                        div2Size = this.config.panes[paneIndex + 1].size + change;
    				    div2.style.width = div2Size + "%";
    			    }
    			    else {
                        if ( e.clientY < div1R.top || e.clientY > div2R.bottom )  {
                            return;
                        }
    			    	newSize = e.clientY / window.innerHeight * 100;
    				    newSize = width;
                        
                        // TODO alter div
    			    }
    			    this.config.panes[paneIndex].size = Math.round(newSize * 100) / 100;
                    this.config.panes[paneIndex + 1].size = Math.round(div2Size * 100) / 100;;
    		    }.bind(this, paneIndex );
        },
        
        openPanel: function(update)    {
            if ( this.basepanel || this.config.OpenToThisPanel )   {
                let panelToOpenTo = this.checkSubOpenPanel(this.config);
                
                // TODO pass onto next panel?
                this.config.panes[panelToOpenTo].tabs[update.key] =
                    update;
                let id = this.panelIDs[panelToOpenTo] + "_"+ update.key;
                this.tabIDs[panelToOpenTo][update.key] = id;
                this.panelKey++;
                this.activeTab = id;
            }
        },
        
        checkSubOpenPanel: function(startConfig)   {
            if ( startConfig.panes )    {
                return startConfig.panes.findIndex(pane => pane.openToThisPanel == true) 
                            || false;
            }
            else    {
                return startConfig.tabs.findIndex(tab => tab.openToThisPanel == true) 
                            || false;
            }
        },
        
        closePanel: function(panelID)   {
            let activeTab = $("#" + panelID + " a.active");
            let tabIndex = activeTab.attr('data-tabindex');
            let panelIndex = this.panelIDs.indexOf(panelID);
            delete this.config.panes[panelIndex].tabs[tabIndex];
            
            // Activate other tab
            let newActive = this.tabIDs[panelIndex].find(value => value != undefined);
            $('a[href="#' + newActive + '"]').tab('show');
            this.activeTab = newActive;
            
            this.panelKey++;
        },
        
        updateMenu: function(update)  {
            if ( this.basepanel )   {
                if ( update.key )  {
                    update.version = this.menuUpdate.version + 1;
                    this.menuUpdate = update;
                }
            }
        },
        
        dropPane: function(event, bar)    {
            event.preventDefault();
            $('.architect-drop').removeClass('architect-drop-over');
            let newPanel = JSON.parse(event.dataTransfer.getData('panel'));
            
            // Add panel in
            if ( bar == TAB_BAR )   {
                this.openPanel(newPanel);
            }
            else if ( bar == SIDE_BAR ) {
                // If horizontal, add new pane to current set
                if ( this.horizontal == true )  {
                    this.addNewPane(newPanel);
                }
                
                // TODO if vertical
            }
                
            // TODO if BOTTOM_BAR
            
            else if ( bar == NEW_TAB ) {
                
                
                // TODO fix
                
                // possible
                //https://stackoverflow.com/questions/28230845/communication-between-tabs-or-windows/43830980#43830980
                
                var newTab = Vue.extend(testtab);
                var instance = new newTab({
                    propsData: { 
                        workspace: this.workspace,
                        config: this.config,
                        panels: this.panels,
                        basepanel: this.basepanel
                    }
                });
                instance.$mount();

            }
        },
        
        dragOver: function (event) {
            event.preventDefault();
            $(event.target).addClass('architect-drop-over');
            event.dataTransfer.dropEffect = "move"
        },
        
        dragLeave: function (event) {
            event.preventDefault();
            $(event.target).removeClass('architect-drop-over');
        },

        addNewPane: function ( event )  {
            let newPane = { tabs: []};
            newPane.tabs[event.key] = {
                ComponentData: event.ComponentData,
                ComponentID: event.ComponentID,
                Title: event.Title,
                key: event.key
            };
            this.config.panes.push(newPane);
            
            this.setPanelSizes();
            
            this.panelKey++;
        },
        
        setOpenPanel: function ( panel )    {
            for ( let paneIndex in this.config.panes) {
                this.config.panes[paneIndex].openToThisPanel = paneIndex == panel;
            }
        },
        
        createTabs: function(createElement, paneIndex, panetabs)  {
            let tabPanes = [];
            let tabPaneIDs = this.tabIDs[paneIndex];
            let activeClass = " show active";
            for ( const tabIndex in panetabs) {
                let componentName = this.panels[panetabs[tabIndex].
                            ComponentID].ComponentName;
                paneElement =createElement('div',
                    { 
                    attrs: {        
                        'workspace': this.workspace,
                        'config': panetabs[tabIndex],
                        'panels': this.panels,
                        'menuupdate': this.menuUpdate,
                        'tabID': tabPaneIDs[tabIndex],
                        'id': tabPaneIDs[tabIndex],
                        'class': "tab-pane fade w-100" + activeClass
                    },
                    on: {
                        'save-workspace': function(update) {
                            this.$emit('save-workspace', update);
                        }.bind(this),
                        'updateMenu': function(update) {
                            this.updateMenu(update);
                        }.bind(this),
                        'openPanel': function(update) {
                            this.openPanel(update);
                        }.bind(this),
                    },
                    is: componentName,
                    key: tabPaneIDs[tabIndex] + this.panelKeys [paneIndex]
                    }
                );
                activeClass = '';
                tabPanes.push(paneElement);
            }
            return tabPanes;
        }
   },
    
    render(createElement) {
		let classPane = 'panel_vertical';
		let sizeDim = 'height';
    	let baseAddClass = '';
    	let resizePaneClass = 'panel_resize_horizontal';
    	if ( this.horizontal == true )	{
    		classPane = 'panel_horizontal';
    		sizeDim = 'width';
        	resizePaneClass = 'panel_resize_vertical';
        	baseAddClass = ' clearfix';
    	}
        
      	let mainHeight = window.innerHeight - 
      			document.getElementById('navbar').offsetHeight;
        let panelList = [];
        for ( let paneIndex in this.config.panes) {
            if ( paneIndex === "OpenToThisPanel")   {
                this.openToThisPanel = paneIndex;
                continue;
            }
            paneIndex = parseInt(paneIndex);
            // Ignore sub panels ATM
            let pane = this.config.panes[paneIndex];

            let paneElement = null;
           
//            let tabPanes = [];
            let tabPaneIDs = this.tabIDs[paneIndex];
            let tabPanes = this.createTabs(createElement, paneIndex, pane.tabs);
//            activeClass = " show active";
//            for ( const tabIndex in pane.tabs) {
//                let componentName = this.panels[pane.tabs[tabIndex].
//                            ComponentID].ComponentName;
//                paneElement =createElement('div',
//                    { 
//                    attrs: {        
//                        'workspace': this.workspace,
//                        'config': pane.tabs[tabIndex],
//                        'panels': this.panels,
//                        'menuupdate': this.menuUpdate,
//                        'tabID': tabPaneIDs[tabIndex],
//                        'id': tabPaneIDs[tabIndex],
//                        'class': "tab-pane fade w-100" + activeClass
//                    },
//                    on: {
//                        'save-workspace': function(update) {
//                            this.$emit('save-workspace', update);
//                        }.bind(this),
//                        'updateMenu': function(update) {
//                            this.updateMenu(update);
//                        }.bind(this),
//                        'openPanel': function(update) {
//                            this.openPanel(update);
//                        }.bind(this),
//                    },
//                    is: componentName,
//                    key: tabPaneIDs[tabIndex] + this.panelKeys [paneIndex]
//                    }
//                );
//                activeClass = '';
//                tabPanes.push(paneElement);
//            }
            if ( Object.keys(pane.tabs).length == 1)    {
                paneElement = tabPanes[0];
                
                let navDrop = createElement('div', {
                    attrs:  {
                        'class': 'architect-nonav-drop architect-drop'
                    },
                    on: {
                        'drop': function(event) {
                            this.setOpenPanel(paneIndex);
                            this.dropPane(event, TAB_BAR);
                        }.bind(this),
                        'dragStart': function(event) {
                            this.dragTest(event);
                        }.bind(this),
                        'dragover': function(event) {
                            this.dragOver(event);
                        }.bind(this),
                        'dragleave': function(event) {
                            this.dragLeave(event);
                        }.bind(this),
                    }
                });
                paneElement = createElement('div',
                    [ tabPanes[0], navDrop ]
                );
            }
            else    {
                // Output tabs header
                let nav = '<ul class="architect-nav nav text-nowrap flex-nowrap nav-tabs architect-drop">';
                let activeClass = ' active';
                for ( const tabIndex in pane.tabs) {
                    nav += '<li class="nav-item">'
                        + '<a href="#' + tabPaneIDs[tabIndex]
                        + '" class="py-0 nav-link' + activeClass 
                        + '" data-toggle="tab" data-tabindex="' + tabIndex
                        + '">' + pane.tabs[tabIndex].Title
                        + '</a>'
                        '</li>';
                    activeClass = '';
                }
                nav += '</ul>';
                let navElement = createElement('div', {
                    attrs:  {
                        'class': 'architect-tab-drop'
                    },
                    domProps: {
                        innerHTML: nav
                    },
                    on: {
                        'drop': function(event) {
                            this.setOpenPanel(paneIndex);
                            this.dropPane(event, TAB_BAR);
                        }.bind(this),
                        'dragStart': function(event) {
                            this.dragTest(event);
                        }.bind(this),
                        'dragover': function(event) {
                            this.dragOver(event);
                        }.bind(this),
                        'dragleave': function(event) {
                            this.dragLeave(event);
                        }.bind(this),
                    }
                });
                
                // Add close panel element
                let tabClose = createElement('img',
                    { 
                        attrs: {  
                            'class': "nav-link-close float-right",
                            'src': "/ui/icons/x.svg"
                        },
                        on: {
                            'click': function(paneIndex) {
                                this.closePanel(this.panelIDs [paneIndex]);
                            }.bind(this, paneIndex)
                        }
                    }
                );
                                
                paneElement = createElement('div', {
                    attrs: {        
                            'class': "tab-content"
                        },
                    },
                    [ tabClose, navElement,  ...tabPanes]
                );
            }

            let newPane = createElement('div',
                { attrs: 
                    { 
                        'class': classPane ,
                        'style': sizeDim + ": " + pane.size + "%",
                        'id': this.panelIDs [paneIndex]
                    }
                }, 
                [paneElement]
            );
            
            panelList.push(newPane);
            
            if ( this.config.sizeable ) {
                let resize = createElement('div',
                    { 
                        attrs: { 'class': resizePaneClass },
                        on: {
                            mousedown: function ( paneIndex, e ) { 
                                return this.resizePane (e, paneIndex);
                            }.bind(this, paneIndex)
                          }
                    }
                ); 
                panelList.push(resize);
           
            }
        }
        // Remove last resize bar if present
        if ( this.config.sizeable ) {
            panelList.pop();
        }
        
        let sideBar = createElement('div',
            { 
                attrs: {  
                    'class': "architect-sidebar-drop architect-drop"
                },
                on: {
                    'drop': function(event) {
                        this.dropPane(event, SIDE_BAR);
                    }.bind(this),
                    'dragStart': function(event) {
                        this.dragTest(event);
                    }.bind(this),
                    'dragover': function(event) {
                        this.dragOver(event);
                    }.bind(this),
                    'dragleave': function(event) {
                        this.dragLeave(event);
                    }.bind(this),
                }
            }
        );
        panelList.push(sideBar);

        let newTabElement = createElement('div', {
            attrs:  {
                'class': 'architect-drop architect-newtab-drop'
            },
            on: {
                'drop': function(event) {
                    this.dropPane(event, NEW_TAB);
                }.bind(this),
                'dragStart': function(event) {
                    this.dragTest(event);
                }.bind(this),
                'dragover': function(event) {
                    this.dragOver(event);
                }.bind(this),
                'dragleave': function(event) {
                    this.dragLeave(event);
                }.bind(this),
            }
        });
        panelList.push(newTabElement);

        // Add drop panel elements
        let footer = createElement('div',
            { 
                attrs: {  
                    'class': "architect-footer-drop architect-drop"
                },
                on: {
                    'drop': function(event) {
                        this.dropPane(event, BOTTOM_BAR);
                    }.bind(this),
                    'dragStart': function(event) {
                        this.dragTest(event);
                    }.bind(this),
                    'dragover': function(event) {
                        this.dragOver(event);
                    }.bind(this),
                    'dragleave': function(event) {
                        this.dragLeave(event);
                    }.bind(this),
                }
            }
        );
        panelList.push(footer);

        return createElement(
          'div', {
              attrs: {
                  'class': 'panel panel_back' + baseAddClass,
                  'style':"height: " + mainHeight + "px",
                  ':key': this.panelKey
              }
          }, panelList
        );
    }
})
