var report = Vue.component('report', {
    data: function() {
        return {
            dateRangePanels: [],
            dateRangePanel: -1,
            datePanelOpen: false,
            sourceSelector: [],
            sourcePanel: -1,
            sourcePanelOpen: false,
            graphPanels: [],
            graphPanel: -1,
            graphUpdateKey: 0,
            graphPanelOpen: false,
            reportName: '',
            sourcesSelected: false,
            sourcesLoaded: false,
            baseURL: null
        }
    },
    
    props: ['workspace', 'config', 'panels', 'tabID'],

    created: function() {
        this.baseURL = window.location.origin;
        this.setDefaults();
        // Find child panels
        for (const [key, panel] of Object.entries(this.panels)) {
            if ( panel.ParentPanelTypeID == this.config.ComponentID ) {
                if ( panel.ParentPanelContext == "DateRange" ) {
                    this.dateRangePanels.push(panel);
                }
                if ( panel.ParentPanelContext == "SourceSelector" ) {
                    this.sourceSelector.push(panel);
                }
                if ( panel.ParentPanelContext == "Graph" ) {
                    this.graphPanels.push(panel);
                }
            }
        };
        
        this.reportName = this.config.title;
        this.graphPanel = this.config.ComponentData.graphPanel;
        this.dateRangePanel = this.config.ComponentData.dateRangePanel;
        this.sourcePanel = this.config.ComponentData.sourcePanel;
        this.sourcePanelOpen = this.config.temp[this.tabID].sourcePanelOpen;
        this.graphPanelOpen = this.config.temp[this.tabID].graphPanelOpen;
        this.datePanelOpen = this.config.temp[this.tabID].datePanelOpen;
        this.loadDataSources();  
        this.sourcesLoaded = this.config.ComponentData.temp.dataSourcesLoaded;
        
//        console.log(this.tabID + "/" + 'created ');
//        console.log(this.config); 
//        console.log(this.datePanelOpen); 
//        console.log(this.config.ComponentData.temp.dataSourcesLoaded); 
    }, 
    
    watch: { 
        reportName:  function(newValue, oldValue) {
            if ( oldValue )    {
                this.updateConfig();
            }
        }
    },
    
    methods: {
        setDefaults: function() {
            this.config.ComponentData.temp ||= {};
            this.config.temp ||= {};
            this.config.temp[this.tabID] ||= { 
                    sourcePanelOpen: false, 
                    graphPanelOpen: false,
                    datePanelOpen: false 
                };
            this.config.ComponentData ||= {};
            this.config.ComponentData.dateRangePanel ||= -1;
            this.config.ComponentData.graphPanel ||= -1;
            this.config.ComponentData.sourcePanel ||= -1;
            if ( ! this.config.ComponentData.startDateTime) {
                let now = new Date();
                let end = new Date();
                end.setSeconds(now.getSeconds() + 1);
                
                this.config.ComponentData.startDateTime = now.toISOString().split('.')[0];
                this.config.ComponentData.endDateTime = end.toISOString().split('.')[0];
            }
            
        },
        
        loadDataSources: function() {
//            console.log(this.tabID + "/" + this.workspace.dataSources )
            // Locate data sources
            if ( this.workspace.dataSources === undefined )    {
                let sources = this.listDataSources(this.workspace.Menu);
                this.workspace.dataSources ||= {};
                if ( ! this.config.ComponentData.dataSources )  {
                    this.config.ComponentData.dataSources = {};
                    // set sources to not selected
                    for ( const id in sources)  {
                        this.config.ComponentData.dataSources[sources[id]] = {selected: false};
                    }
                }

                let request = $.ajax({
                    url: "/listLoads/" + JSON.stringify(sources),
                    headers: { 
                        Accept: "application/json",
                        "Authorization": "Bearer " + Cookies.get('JWT') 
                    },
                    type: "get",
                });
                request.done(function(response) {
                    for ( const id in response )    {
                        this.workspace.dataSources[id] = response[id];
                        this.workspace.dataSources[id].data = {};
                    }
                    
                    this.config.ComponentData.temp.dataSourcesLoaded = true;
                    this.sourcesLoaded = true;
//                    console.log("origin " +this.tabID )
                    this.$emit('updateMenu', {key: this.config.key,
                        ComponentData: this.config.ComponentData,
                        origin: this.tabID
                    });
                    this.updateConfig();
            
                    this.checkSourcesSelected();
                }.bind(this));  
            } 
            else  {
//                console.log(this.config.ComponentData.dataSourcesLoaded )
                if ( this.config.ComponentData.temp.dataSourcesLoaded === true )  {
                    this.checkSourcesSelected();
                }
            }

        },
        
        listDataSources: function(source) {
            let sources = [];
            source.forEach(function(menuItem)   {
                if ( menuItem.data.ComponentData?.loadID ) {
                    sources.push(menuItem.data.ComponentData.loadID);
                }
                if ( menuItem.children ) {
                    sources = sources.concat(this.listDataSources(menuItem.children));
                }
            }.bind(this));
            return sources;
        },
        
        updateGraph: function()  {
        },
        
        toggleDatePickerButton: function()  {
            this.datePanelOpen = ! this.datePanelOpen;
            this.config.temp[this.tabID].datePanelOpen = 
                ! this.config.temp[this.tabID].datePanelOpen;
        },
        
        toggleReportInfoButton: function()  {
            $('#rp-infoselect-open-' + this.tabID).toggle();
            $("#rp-infoselect-close-" + this.tabID).toggle();
        },
        
        toggleSourcesButton: function()  {
            this.sourcePanelOpen = ! this.sourcePanelOpen;
            this.config.temp[this.tabID].sourcePanelOpen = 
                ! this.config.temp[this.tabID].sourcePanelOpen;
        },
        
        toggleReportsButton: function()  {
            this.graphPanelOpen = ! this.graphPanelOpen;
            this.config.temp[this.tabID].graphPanelOpen = 
                ! this.config.temp[this.tabID].graphPanelOpen;
        },
        
        updateSelector: function()  {
            this.config.ComponentData.dateRangePanel = this.dateRangePanel;
            this.config.ComponentData.sourcePanel = this.sourcePanel;
            this.config.ComponentData.graphPanel = this.graphPanel;
            this.updateConfig();
        },
        
        updateDateRange: function(dateStart, dateEnd) {
            this.config.ComponentData.startDateTime = dateStart;
            this.config.ComponentData.endDateTime = dateEnd;
            this.updateConfig();
            this.graphUpdateKey++;
        },
        
        updateSourceSelect: function(sourceID, select)  {
            if ( sourceID === null )   {
                this.config.ComponentData.dataSources = select;
            }
            else   {
                this.config.ComponentData.dataSources[sourceID].selected = select;
            }
            this.graphUpdateKey++;
            this.updateConfig();
            this.checkSourcesSelected();
        },
        
        updateConfig: function() {
//            console.log('updateConfig')
            this.$emit('updateMenu', {key: this.config.key,
                ComponentData: this.config.ComponentData,
                origin: this.tabID
            });
        },
        
        checkSourcesSelected: function()    {
            this.sourcesSelected = false;
            for (const source in this.workspace.dataSources) {
                if ( this.config.ComponentData.dataSources[source].selected )  {
                    this.sourcesSelected = true;
                    break;
                }
            }

        }
    },
    
    template: `
    <div class="container-fluid architect-pane">
            <div class="d-flex flex-row">
                <div class="p-2">
                    Report {{config.title}}
                </div>
                <div class="p-2">
                    <button class="btn btn-outline-secondary btn-sm" 
                            type="button" data-toggle="collapse" 
                            :data-target="'#rp-reportinfo-' + tabID" 
                            aria-expanded="false" 
                            aria-controls="daterange"
                            :id="'rp-reportinfoselection-' + tabID"
                            @click="toggleReportInfoButton">
                        Report Details
                        <img :src="baseURL + '/ui/icons/arrow-bar-down.svg'"
                            :id="'rp-infoselect-open-' + tabID"/>
                        <img :src="baseURL + '/ui/icons/arrow-bar-up.svg'"
                            :id="'rp-infoselect-close-' + tabID" 
                            style="display:none;" />
                    </button>
                </div>
                <div class="p-2">
                    <button class="btn btn-outline-secondary btn-sm" 
                            type="button" data-toggle="collapse" 
                            :data-target="'#rp-sources-' + tabID" 
                            aria-expanded="false" 
                            aria-controls="sourcesSelection"
                            :id="'rp-sourcesSelection-' + tabID"
                            @click="toggleSourcesButton"
                            v-if="config.ComponentData.temp.dataSourcesLoaded">
                        Data Sources
                        <img :src="baseURL + '/ui/icons/arrow-bar-down.svg'"
                            :id="'rp-sources-open-' + tabID" 
                            v-if="! sourcePanelOpen" />
                        <img :src="baseURL + '/ui/icons/arrow-bar-up.svg'"
                            :id="'rp-sources-close-' + tabID" 
                            v-else />
                    </button>
                </div>
                <div class="p-2">
                    <button class="btn btn-outline-secondary btn-sm" 
                            type="button" data-toggle="collapse" 
                            :data-target="'#rp-daterange-' + tabID" 
                            aria-expanded="false" 
                            aria-controls="daterange"
                            :id="'rp-dateSelection-' + tabID"
                            @click="toggleDatePickerButton"
                            v-if="sourcesSelected">
                        Date range
                        <img :src="baseURL + '/ui/icons/arrow-bar-down.svg'"
                            :id="'rp-dateselect-open-' + tabID" 
                            v-if="! datePanelOpen" />
                        <img :src="baseURL + '/ui/icons/arrow-bar-up.svg'"
                            :id="'rp-dateselect-close-' + tabID" 
                            v-else />
                    </button>
                </div>
                <div class="p-2">
                    <button class="btn btn-outline-secondary btn-sm" 
                            type="button" data-toggle="collapse" 
                            :data-target="'#rp-reports-' + tabID" 
                            aria-expanded="false" 
                            aria-controls="daterange"
                            :id="'rp-reportSelection-' + tabID"
                            @click="toggleReportsButton"
                            v-if="sourcesSelected">
                        Report
                        <img :src="baseURL + '/ui/icons/arrow-bar-down.svg'"
                            :id="'rp-report-open-' + tabID"  
                            v-if="! graphPanelOpen" />
                        <img :src="baseURL + '/ui/icons/arrow-bar-up.svg'"
                            :id="'rp-report-close-' + tabID" 
                            v-else  />
                    </button>
                </div>
            </div>
            <div class="d-flex flex-row">
                <div class="collapse container-fluid" 
                        :id="'rp-reportinfo-' + tabID">
                    <div class="card card-body">
                        Report info.
                        <div class="d-flex flex-row">
                            <label for="rp-loadName" class="col-form-label col-form-label-sm">
                                Name
                            </label>
                            <div class="col-sm-3">
                                <input type="text" 
                                    class="form-control-sm" 
                                    :id="'rp-loadName-' + tabID"
                                    required 
                                    max="45" min="1"
                                    v-model="reportName" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-row" v-if="sourcePanelOpen"> 
                <div class="container-fluid" 
                        :id="'rp-sources-' + tabID">
                    <div class="card card-body">
                        <div class="d-flex flex-row">
                            <label for="rp-sourceSelectorType" class="col-form-label-sm">
                                Source selector
                            </label>
                            <div class="col-sm-3">
                                <select :id="'rp-sourceSelectorType-' + tabID"
                                        class="form-control form-control-sm"
                                        v-model="sourcePanel"
                                        @change="updateSelector">
                                    <option value="-1" disabled selected>Source selector</option>
                                    <option v-for="(panel, key) in sourceSelector" v-bind:value="key">
                                        {{ panel.Name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-row" v-if="sourcePanel != -1">
                            <component :is="sourceSelector[sourcePanel].ComponentName" 
                                :key="sourceSelector[sourcePanel].ComponentName.name"
                                :config="config"
                                :workspace="workspace"
                                :tabID="tabID"
                                @updateSourceSelect="updateSourceSelect">
                            </component>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-row" v-if="datePanelOpen">
                <div class="container-fluid" 
                        :id="'rp-daterange-' + tabID">
                    <div class="card card-body">
                        <div class="d-flex flex-row">
                            <label for="rp-dateRangeType" class="col-form-label-sm">
                                Date range
                            </label>
                            <div class="col-sm-3">
                                <select :id="'rp-dateRangeType-' + tabID"  
                                        class="form-control form-control-sm"
                                        v-model="dateRangePanel"
                                        @change="updateSelector">
                                    <option value="-1" disabled selected>Range selector</option>
                                    <option v-for="(panel, key) in dateRangePanels" v-bind:value="key">
                                        {{ panel.Name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-row" v-if="dateRangePanel != -1">
                           <component :is="dateRangePanels[dateRangePanel].ComponentName" 
                                :key="dateRangePanels[dateRangePanel].ComponentName.name"
                                :config="config" 
                                :workspace="workspace"
                                :tabID="tabID"
                                @updateDateRange="updateDateRange"></component>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex flex-row" v-if="sourcesLoaded && graphPanelOpen">
                <div class="container-fluid" 
                        :id="'rp-reports-' + tabID">
                    <div class="card card-body">
                        <div class="d-flex flex-row">
                            <label for="rp-sourceSelectorType" class="col-form-label-sm">
                                Reports
                            </label>
                            <div class="col-sm-3">
                                <select :id="'rp-graphSelectorType-' + tabID" 
                                        class="form-control form-control-sm"
                                        v-model="graphPanel"
                                        @change="updateSelector">
                                    <option value="-1" disabled selected>Source selector</option>
                                    <option v-for="(panel, key) in graphPanels" v-bind:value="key">
                                        {{ panel.Name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-row" v-if="graphPanel != -1">
                            <component :is="graphPanels[graphPanel].ComponentName" 
                                :key="graphUpdateKey"
                                :config="config"
                                :workspace="workspace"
                                :tabID="tabID">
                            </component>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    `
})
