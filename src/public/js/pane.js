const SIDE_BAR = 1;
const TAB_BAR = 2;
const BOTTOM_BAR = 3;
const NEW_TAB = 4;

var pane = Vue.component('pane', {
    data: function() {
        return {
        	horizontal: false,
            menuUpdate: { version: 0 },
            panelIDs: [],
            tabIDs: [],
            tabKeys: [],
            openToThisPanel: false,
            panelKey: 0,
            paneID: '',
            tabMonitor: null,
            
            windowRef: null,
            comChannel: null,
            newPaneInstance: null,
            baseURL: null
        }
    },
    
    props: ['workspace', 'config', 'panels', 'basepanel', 'panes'],
    
    created: function() {
        console.log("created - set"); 
        this.baseURL = window.location.origin;
        // TODO horizontal- this needs to go with the pane?
               
        this.horizontal = this.workspace.Config.horizontal;
        this.paneID = "main_pane_" + Math.floor(Math.random() * 100000);
        this.setPanelSizes();
        
        this.workspace.activeTab = this.workspace.activeTab || {};
        
        window.addEventListener("resize", function()    {
            let mainHeight = window.innerHeight - 
                document.getElementById('navbar').offsetHeight;
            if ( document.getElementById(this.paneID) ) {
                document.getElementById(this.paneID)
                    .setAttribute("style","height: " + mainHeight + "px");
            }
        }.bind(this));
        
        this.comChannel = new BroadcastChannel('Architect-perf');
        this.comChannel.onmessage = this.receiveUpdate;
    }, 
 
    mounted: function () {
        if ( this.basepanel === false ) {
//            console.log("open new window");
            this.windowRef = window.open("");
        
            this.windowRef.addEventListener('beforeunload', this.closePortal);
//            console.log(this.config);       
            // Copy header stuff, including style sheets etc.  
            let children = document.getElementsByTagName('head')[0].children;
            for (var i = 0; i < children.length; i++) {
                this.windowRef.document.head.appendChild (children[i].cloneNode(true));
            }
            let baseDiv = this.windowRef.document.body.appendChild(document.createElement("div"));
            baseDiv.setAttribute('id', 'app');
            baseDiv.appendChild(this.$el);
       }
    },
    
    updated: function() {
        // Monitor the tabs to keep track of active tab
        if ( this.tabMonitor === null)   {
            this.tabMonitor = new MutationObserver(function(mutationsList){
                for(const mutation of mutationsList) {
                    if ( mutation.target.classList.contains("show") ) {
                        let idParts = mutation.target.id.split("_");
                        this.workspace.activeTab[idParts[0] +"_" + idParts[1]] = mutation.target.id;
                    } 
                }
            }.bind(this));
        }
        else    {
            this.tabMonitor.disconnect();
        }
            
        $(".tab-pane").each(function(index, obj)  {
            this.tabMonitor.observe(obj, { 
                attributes: true, attributeFilter: [ "class" ],
                childList: false 
            });
        }.bind(this));
    },

    methods: {
        setPanelSizes: function()   {
            // Need to add all known sizes and then divide remaining up
            // between amount of unknown sizes
            let totalSizeSet = 0;
            let sizeNotSet = [];
            let totalPanes = 0;
            for ( const paneIndex in this.panes) {
                let pane = this.panes[paneIndex];
                totalPanes++;
                // If pane size set - but not an autosized panel
                if ( pane.size &&  !pane.autoSized )    {
                    totalSizeSet += pane.size;
                }
                else    {
                    sizeNotSet.push(paneIndex);
                }
                let id = "pane_" + Math.floor(Math.random() * 100000);
                this.panelIDs.push(id);
                if ( pane.children && this.tabIDs[paneIndex] === undefined )    {
                    this.tabIDs[paneIndex] = [];
                    this.tabKeys[paneIndex] = [];
                    for ( const tabIndex in pane.children) {
                        let key = pane.children[tabIndex].key;
                        this.tabIDs[paneIndex][key] = id + "_" + key;
                        this.tabKeys[paneIndex][key] = 0;
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
                this.panes.forEach ( function(pane) {
                    if ( ! pane.size || pane.autoSized == true )    {
                        pane.size = remainSize;
                        pane.autoSized = true;
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
                        let change = this.panes[paneIndex].size - newSize;
                        // As pane has been sized manually, remove autosize flag
                        this.panes[paneIndex].autoSized = false;
    				    div1.style.width = newSize + "%";
                        div2Size = this.panes[paneIndex + 1].size + change;
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
    			    this.panes[paneIndex].size = Math.round(newSize * 100) / 100;
                    this.panes[paneIndex + 1].size = Math.round(div2Size * 100) / 100;;
    		    }.bind(this, paneIndex );
        },
        
        openPanel: function(update)    {
            if ( this.basepanel || this.config.OpenToThisPanel )   {
                let panelToOpenTo = this.checkSubOpenPanel();
                
                // TODO pass onto next panel?
                
                
//                let newIndex = this.panes[panelToOpenTo].children.push({
//                    key: update.key,
//                    type: 'tab',
//                    title: update.title,
//                    ...update
//                });
//                
//                let id = this.panelIDs[panelToOpenTo] + "_"+ update.key;
//                this.tabIDs[panelToOpenTo][newIndex - 1] = id;
                let newIndex = this.panes[panelToOpenTo].children[update.key] = {
                    key: update.key,
                    type: 'tab',
                    title: update.title,
                    ...update
                };
                
                let id = this.panelIDs[panelToOpenTo] + "_"+ update.key;
                this.tabIDs[panelToOpenTo][update.key] = id;
                this.workspace.activeTab[this.panelIDs[panelToOpenTo]] = id;
                this.panelKey++;
            }
        },
        
        checkSubOpenPanel: function()   {
            return this.panes.findIndex(pane => pane.openToThisPanel == true) 
                || false;
        },
        
        closePanel: function(panelID)   {
            let panelIndex = this.panelIDs.indexOf(panelID);
            let activeTab = $("#" + panelID + " a.active");
            let tabIndex = activeTab.attr('data-tabindex');
            // Only set when multiple tabs open
            if ( tabIndex ) {
                delete this.panes[panelIndex].children[tabIndex];
                // Activate other tab
                let newActive = this.tabIDs[panelIndex].find(value => value != undefined);
                $('a[href="#' + newActive + '"]').tab('show');
                this.workspace.activeTab[panelID] = newActive;
            }
            if ( !tabIndex || this.panes[panelIndex].children.length === 0 ) {
                delete this.panes[panelIndex];
                this.setPanelSizes();
            }
            this.$emit('save-workspace');
            this.panelKey++;
        },
        
        receiveUpdate: function (update)    {
            console.log(update);
            this.updateMenu(JSON.parse(update.data));
        },
        
        updateMenu: function(update)  {
            console.log(update);
            if ( this.basepanel && update.key )  {
                update.version = this.menuUpdate.version + 1;
                this.menuUpdate = update;
            }
            if ( update.key && update.origin )   {
                // Flag update to all panels with same key
                
                // TODO sub-panels, new windows
                
                for ( const paneID in this.panes)    {
                    let tabID = update.origin.split(/[_]/).pop();
                    if ( this.tabKeys[paneID][tabID] != undefined
                             && update.origin != this.tabIDs[paneID][tabID])  {
                        for ( const child in this.panes[paneID].children )  {
                            if ( this.panes[paneID].children[child].key == tabID )  {
                                this.panes[paneID].children[child].ComponentData = 
                                        update.ComponentData;
                            }
                        }
                        this.tabKeys[paneID][tabID]++;
                    }
                }
            }
        },
        
        dropPane: function(event, bar)    {
            event.preventDefault();
            $('.architect-drop').removeClass('architect-drop-over');
//            console.log(event.dataTransfer.getData('panel'));
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
                newPanel['type'] = 'tab';
                let newWindow =
                    {
                        children: [
                            {
                                type: 'pane',
                                size: 100,
                                children: [newPanel]
                            }
                        ],
                        type: 'window'
                    };
                this.workspace.Windows.push(newWindow);
                var newTab = Vue.extend(pane);
                var instance = new newTab({
                    propsData: { 
                        workspace: this.workspace,
                        config: this.config,
                        panels: this.panels,
                        basepanel: false,
                        panes: newWindow['children']
                    }
                });
                console.log(this.config);              
                instance.$mount();
            }
            this.$emit('save-workspace');
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
        dragStart: function (event) {
            let tabIndex = event.currentTarget.getAttribute("data-tabindex");
            let paneIndex = event.currentTarget.getAttribute("data-paneindex");
            let config = this.panes[paneIndex].children[tabIndex];
            let newPanel =  { title: config.title,
                                key: config.key,
                                ComponentID: config.ComponentID,
                                ComponentData: config.ComponentData
                            };

            event.originalEvent.dataTransfer.setData('panel', 
                                JSON.stringify(newPanel));
            $('.architect-drop').addClass('architect-drop-active');
            $('.architect-drop').removeClass('architect-drop');
        },
        dragEnd: function () {
            $('.architect-drop-active').addClass('architect-drop')
            $('.architect-drop').removeClass('architect-drop-active')
        },
        
        addNewPane: function ( event )  {
            let newPane = { 
                children: [
                    {
                        ComponentData: event.ComponentData,
                        ComponentID: event.ComponentID,
                        title: event.title,
                        key: event.key,
                        type: 'tab'
                    }
                ],
                type: 'pane'
            };
            this.panes.push(newPane);
            this.setPanelSizes();
            this.panelKey++;
        },
        
        setOpenPanel: function ( panel )    {
            for ( let paneIndex in this.panes) {
                this.panes[paneIndex].openToThisPanel = paneIndex == panel;
            }
        },
        
        removeCloseButton: function(paneIndex)  {
            $("#close_" + this.panelIDs [paneIndex]).remove();
        },
        
        noDrop: function(paneIndex)  {
            $("#" + this.panelIDs [paneIndex] + " .architect-nonav-drop").remove();
        },
       
        createTabs: function(createElement, paneIndex, panetabs)  {
            let tabPanes = [];
            let tabPaneIDs = this.tabIDs[paneIndex];
            let activeTab = this.workspace.activeTab[this.panelIDs[paneIndex]] || 
                                Object.values(tabPaneIDs)[0];
            for ( const tabIndex in panetabs) {
                if ( panetabs[tabIndex].type != 'tab')  {
                    continue;
                }
                let key = panetabs[tabIndex].key;
                let extraClass = ((tabPaneIDs[key] == activeTab ) 
                        ? ' show active' : '') 
                let componentName = this.panels[panetabs[tabIndex].ComponentID].ComponentName;
                 paneElement =createElement('div',
                    { 
                    attrs: {        
                        menuupdate: this.menuUpdate,
                        id: tabPaneIDs[key],
                        class: "tab-pane fade w-100" + extraClass
                    },
                    props:  {
                        workspace: this.workspace,
                        config: panetabs[tabIndex],
                        panels: this.panels,
                        tabID: tabPaneIDs[key],
                    },
                    on: {
                        'save-workspace': function(update) {
                            this.$emit('save-workspace', update);
                        }.bind(this),
                        'updateMenu': function(update) {
                            this.updateMenu(update);
                            this.comChannel.postMessage(JSON.stringify(update));
                        }.bind(this),
                        'openPanel': function(update) {
                            this.openPanel(update);
                        }.bind(this),
                        'noClose': function(paneIndex)   {
                            this.removeCloseButton(paneIndex);
                        }.bind(this, paneIndex),
                        'noDrop': function(paneIndex)   {
                            this.noDrop(paneIndex);
                        }.bind(this, paneIndex)
                    },
                    is: componentName,
                    key: tabPaneIDs[key] + "_" +
                            this.tabKeys[paneIndex][key],
                    }
                );
                activeClass = '';
                tabPanes.push(paneElement);
            }
            return tabPanes;
        },
        
        baseDropElement: function ( createElement, paneIndex, cssClass, dropType)   {
            return createElement('div', {
                attrs:  {
                    'class': cssClass
                },
                on: {
                    'drop': function(event) {
                        this.setOpenPanel(paneIndex);
                        this.dropPane(event, dropType);
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
        },
        
        createTabHeader: function(createElement, paneIndex, panetabs)   {
            let tabPaneIDs = this.tabIDs[paneIndex];
            let nav = '<ul class="architect-nav nav text-nowrap flex-nowrap nav-tabs architect-drop">';
            let activeTab = this.workspace.activeTab[this.panelIDs[paneIndex]] || 
                                Object.values(tabPaneIDs)[0];
//console.log(this.workspace.activeTab);
            for ( const tabIndex in panetabs) {
                let key = panetabs[tabIndex].key;
                nav += '<li class="nav-item">'
                        + '<a href="#' + tabPaneIDs[key]
                        + '" class="py-0 nav-link'
                        + ((tabPaneIDs[key] == activeTab ) ? ' active' : '') 
                        + '" data-toggle="tab" data-paneindex="' + paneIndex
                        + '" data-tabindex="' + tabIndex
                        + '">' + panetabs[tabIndex].title
                        + '</a>'
                        '</li>';
            }
            nav += '</ul>';
            
            return createElement('div', {
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
        },
        
        closePanelTag: function(createElement, paneIndex)  {
            return createElement('img',
                { 
                    attrs: {  
                        class: "nav-link-close float-right",
                        id: "close_" + this.panelIDs [paneIndex],
                        src: this.baseURL + "/ui/icons/x.svg"
                    },
                    on: {
                        'click': function(paneIndex) {
                            this.closePanel(this.panelIDs [paneIndex]);
                        }.bind(this, paneIndex)
                    }
                }
            );
        },
        
        resizePanel: function(createElement, paneIndex, resizePaneClass)  {
            return createElement('div',
                { 
                    attrs: { 'class': resizePaneClass },
                    on: {
                        mousedown: function ( paneIndex, e ) { 
                            return this.resizePane (e, paneIndex);
                        }.bind(this, paneIndex)
                    }
                }
            ); 
        }
    },
    
    render(createElement) {
//        console.log("render");
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
//        console.log(this.panes);
        for ( let paneIndex in this.panes) {
            paneIndex = parseInt(paneIndex);
            // TODO Ignore sub panels ATM
            let pane = this.panes[paneIndex];

            let paneElement = null;
            
            let tabClose = this.closePanelTag(createElement, paneIndex);
            let tabPanes = this.createTabs(createElement, paneIndex, pane.children);
            if ( Object.keys(pane.children).length == 1)    {
                paneElement = tabPanes[0];
                let navDrop = this.baseDropElement(createElement, paneIndex,
                    'architect-nonav-drop architect-drop', TAB_BAR);;
                paneElement = createElement('div',
                    [ tabClose, tabPanes[0], navDrop ]
                );
            }
            else    {
                // Create tabs header
                let navElement = this.createTabHeader ( createElement, 
                        paneIndex, pane.children);
                                
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
                panelList.push(this.resizePanel (createElement, 
                        paneIndex, resizePaneClass));
            }
        }
        // Remove last resize bar if present
        if ( this.config.sizeable ) {
            panelList.pop();
        }
        
        panelList.push(this.baseDropElement(createElement, null,
                "architect-sidebar-drop architect-drop", SIDE_BAR));

        panelList.push(this.baseDropElement(createElement, null,
                'architect-drop architect-newtab-drop', NEW_TAB));

        // Add drop panel elements
        panelList.push(this.baseDropElement(createElement, null,
                "architect-footer-drop architect-drop", BOTTOM_BAR));

        content = createElement(
          'div', {
              attrs: {
                  'class': 'panel panel_back' + baseAddClass,
                  'style':"height: " + mainHeight + "px",
                  ':key': this.panelKey,
                  'id': this.paneID
              }
          }, panelList
        );
        
        $('a.py-0.nav-link').attr('draggable', true)
            .bind('dragstart', this.dragStart.bind(this))
            .bind('dragend', this.dragEnd.bind(this));  

       return content;
    }
})
