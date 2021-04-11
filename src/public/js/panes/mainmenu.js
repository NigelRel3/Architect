var pane = Vue.component('mainmenu', {
    data: function() {
        return {
        	nextKey: null,
        	workspaceNodeKey: null,
        	tree: null
        }
    },
    
    props: ['workspace', 'config', 'panels', 'menuupdate'],
    
    computed: {
        menuChange: function() {
            return this.menuupdate.version;
        },
    },
    
    watch: { 
        menuChange:  function() {
            // Update menu
            let menuNode = this.tree.getNodeByKey(this.menuupdate.key.toString());
            if ( this.menuupdate.name ) {
                menuNode.setTitle(this.menuupdate.name);
                if ( this.menuupdate.key  === this.workspaceNodeKey)    {
                    this.config.ComponentData.menu[0].title = this.menuupdate.name;
                }
                this.menuupdate.name = null;
            }
            menuNode.data.ComponentData = { 
                ...menuNode.data.ComponentData,
                ...this.menuupdate.ComponentData
            };
            this.menuupdate.ComponentData = null;
            this.saveMenu();
        }
    },
    
    mounted: function () {
        this.$emit('noClose');
        this.$emit('noDrop');
		this.nextKey = this.workspace.Menu[0].data.MenuData.nextKey;
		this.displayToolBar();
        
    	$("#main-menu-tree").fancytree({
            extensions: ["edit"],
            
            edit: {
                triggerStart: ["f2", "shift+click", "mac+enter"],
                close: function(event, data) {
      				// Check for duplicates
                	let exists = data.node.parent.children.some(menuItem =>
                		menuItem.title == data.node.title && menuItem != data.node
                	);
                	if ( exists )	{
                		alert("Please enter a unique name");
                		data.node.editStart();
                	}
                	// update workspace name if changed
                	if ( data.node.key == this.workspaceNodeKey )	{
                		this.workspace.Name = data.node.title;
                	}
                    data.node.setActive();
                    // Save new menu
                    this.saveMenu();
                }.bind(this)
            },
            
            // Save data after change
            modifyChild: function() {
            	this.saveMenu();
			}.bind(this),
			
            source: this.workspace.Menu,
            
            // Set appropriate toolbar buttons for this item
            activate: function( event, data )	{
            	let node = data.node;
            	if ( node.isActive() )	{
            		let btns = { 'mainmenu-toolbar-edit' : node.data.MenuData.editable,
            				'mainmenu-toolbar-add' : node.data.MenuData.addContents,
            				'mainmenu-toolbar-add-folder' : node.data.MenuData.addFolder,
            				'mainmenu-toolbar-delete' : node.data.MenuData.deleteable,
                            'mainmenu-toolbar-open' : node.data.MenuData.ComponentID
            				};
            		Object.keys(btns).forEach(function (option) {
                    	if ( btns[option] )	{
                    		$('#' + option).removeClass("main-menu-button-disabled");
                    	}
                    	else	{
                    		$('#' + option).addClass("main-menu-button-disabled");
                    	}
            		});
            	}
        	}.bind(this),
            
            dblclick: function(event, data )    {
                let node = data.node;
                if ( node.data.MenuData.ComponentID )    {
                    let newPanel = {Title: node.title,
                        key: node.key,
                        ComponentID: node.data.MenuData.ComponentID,
                        ComponentData: node.data.ComponentData
                    };
                    this.$emit('openPanel', newPanel);
                }
            }.bind(this),
            
            renderNode: function(event, data)  {
                if ( data.node.data.MenuData.ComponentID )  {
                    $(data.node.li).attr('draggable', true)
                        .bind('dragstart', function (event) {
                            let dragNode = $.ui.fancytree.getNode(event);
                            
                            let newPanel =  { title: dragNode.title,
                                key: dragNode.key,
                                ComponentID: dragNode.data.MenuData.ComponentID,
                                ComponentData: dragNode.data.ComponentData
                            };

                            event.originalEvent.dataTransfer.setData('panel', 
                                JSON.stringify(newPanel));
                            $('.architect-drop').addClass('architect-drop-active');
                            $('.architect-drop').removeClass('architect-drop');
                        })
                        .bind('dragend', function () {
                            $('.architect-drop-active').addClass('architect-drop')
                            $('.architect-drop').removeClass('architect-drop-active')
                        }
                    );                     
                }
            }.bind(this),
           
        });
    		
    	// Store workspace name key
    	this.tree = $.ui.fancytree.getTree(".main-menu-tree");
        let workspaceNode = this.tree.findFirst(this.workspace.Name);
    	this.workspaceNodeKey = workspaceNode.key;
        workspaceNode.setActive();
        
        // Save menu structure to workspace
        let d = this.tree.toDict();
        d[0].data.nextKey = this.nextKey;
        this.workspace.Menu = d;
    },
    
    methods: {
    	deleteMenu: function (menuItem){
    		console.log(menuItem);
    	},
    	
    	saveMenu: function (){
    		let d = this.tree.toDict();
    		d[0].data.nextKey = this.nextKey;
    		this.workspace.Menu = d;
    		
    		this.$emit('save-workspace');
		},
		
		displayToolBar: function()	{
			let toolBar = $('#main-menu-toolbar');
            let openButton = $('<div class="main-menu-button-disabled" id="mainmenu-toolbar-open"><img src="/ui/icons/file-earmark-play.svg" alt="" title="open" /></div>');
            openButton.click(function() {
                let tree = $.ui.fancytree.getTree("#main-menu-tree");
                // If set, update pane 2 with component
                if ( tree.activeNode.data.MenuData.ComponentID )    {
                    let newPanel =  { Title: tree.activeNode.title,
                        key: tree.activeNode.key,
                        ComponentID: tree.activeNode.data.MenuData.ComponentID,
                        ComponentData: tree.activeNode.data.ComponentData
                    };
                    this.$emit('openPanel', newPanel);
                }
            }.bind(this));
    		let addButton = $('<div class="main-menu-button-disabled" id="mainmenu-toolbar-add"><img src="/ui/icons/file-earmark-plus.svg" alt="" title="add" /></div>');
    		addButton.click(function()	{
        		let tree = $.ui.fancytree.getTree("#main-menu-tree");
                
        		if ( tree.activeNode.data.MenuData.addContents )	{
                    let newData = { MenuData : 
                        jQuery.extend(true, {}, tree.activeNode.data.MenuData.addChildData)};
                    // Reset options which aren't valid
                    newData.MenuData.addFolder = false;
                    newData.MenuData.addContents = false;
                    newData.ComponentData = [];
	        		tree.activeNode.editCreateNode("child", {
	    				title: "",
	    				key: this.nextKey++,
	    				icon: "/ui/icons/" + tree.activeNode.data.MenuData.addIcon + ".svg",
                        data : newData
	    			});
        		}
    		}.bind(this));
    		let addFolderButton = $('<div class="main-menu-button-disabled" id="mainmenu-toolbar-add-folder"><img src="/ui/icons/folder-plus.svg" alt="" title="add folder" /></div>');
    		addFolderButton.click(function()	{
        		let tree = $.ui.fancytree.getTree("#main-menu-tree");
        		if ( tree.activeNode.data.MenuData.addFolder )	{
                    let newData = { MenuData : 
                        jQuery.extend(true, {}, tree.activeNode.data.MenuData.addChildData)};
                    // Copy config for creation of sub items
                    newData.MenuData = { addChildData : tree.activeNode.data.MenuData.addChildData};
                    // Reset options which aren't valid
                    newData.ComponentID = null;
                    newData.ComponentData = [];
	        		tree.activeNode.editCreateNode("child", {
	    				title: "",
	    				key: this.nextKey++,
                        data : newData,
	    	            folder: true
	    			});
        		}
    		}.bind(this));
    		let editButton = $('<div class="main-menu-button-disabled" id="mainmenu-toolbar-edit"><img src="/ui/icons/pencil.svg" alt="" title="edit" /></div>');
    		editButton.click(function()	{
        		let tree = $.ui.fancytree.getTree("#main-menu-tree");
        		if ( tree.activeNode.data.MenuData.editable )	{
        			tree.activeNode.editStart();
        		}
    		});
    		let deleteButton = $('<div class="main-menu-button-disabled" id="mainmenu-toolbar-delete"><img src="/ui/icons/file-earmark-x.svg" alt="" title="delete" /></div>');
    		deleteButton.click(function()	{
        		let tree = $.ui.fancytree.getTree("#main-menu-tree");
        		if ( tree.activeNode.data.MenuData.deleteable )	{
        			tree.activeNode.remove();
        		}
    		});
    		
            toolBar.append(openButton);
    		toolBar.append(addButton);
    		toolBar.append(addFolderButton);
    		toolBar.append(editButton);
    		toolBar.append(deleteButton);
		}
	},
    
	template:
		`
		<div id="main-menu-container">
			<div class="main-menu-toolbar clearfix" id="main-menu-toolbar"></div>
			<div class="main-menu-tree" id="main-menu-tree"></div>			
		</div>
		`
})
