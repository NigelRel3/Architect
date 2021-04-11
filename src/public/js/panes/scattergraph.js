/*
Charts using https://www.chartjs.org
 */
var scattergraph = Vue.component('scattergraph', {
    data: function() {
        return {
            chartElement: null,
            data: [],
            dataToLoad: 0,
            sourceNames: []
        }
    },
    
    props: ['config', 'workspace', 'tabID'],

    mounted: function() {
        let dataToLoad = this.getDataToLoad();
        
        // Only if new data to load
        if ( Object.keys(dataToLoad).length > 0)   {
            for ( const sourceType in dataToLoad )    {
                let typesToLoad = dataToLoad[sourceType];
                
                // Request data
                let request = $.ajax({
                    url: "/statsData/" + sourceType + "/" + JSON.stringify(typesToLoad),
                    headers: {
                        Accept: "application/json",
                        Authorization: "Bearer " + Cookies.get('JWT')
                    },
                    type: "get"
                });
                this.dataToLoad++;
                            
                request.done(function(sourceType, response) {
                    this.extractTypeData(response, sourceType);
                    this.dataToLoad--;
                        
                    // Wait to all data sources loaded
                    if ( this.dataToLoad == 0 )   {
                            this.dataLoadComplete();
                    }
                }.bind(this, sourceType)); 
                    
            }
        }
        else    {
            this.dataLoadComplete();
        }       

    },
    
    methods: {
        extractTypeData: function(data, sourceType) {
            if ( ! this.config.ComponentData.dataSources[sourceType].color )  {
                this.config.ComponentData.dataSources[sourceType].color = [];
            }
            for ( const subset in data )    {
                if ( !this.workspace.dataSources[sourceType].data[subset] ) {
                    this.workspace.dataSources[sourceType].data[subset] = {};
                }
                if ( ! this.config.ComponentData.dataSources[sourceType].color[subset] )  {
                    this.config.ComponentData.dataSources[sourceType].color[subset] = this.randomColor();
                }
                for (const statType in data[subset])    {
                    let graphData = { labels: [], data: [], 
                        colour: this.config.ComponentData.dataSources[sourceType].color[subset]
                    };
                    data[subset][statType].forEach (function (point) {
                        graphData.data.push( { 
                            x: parseInt(point.Timestamp), 
                            y: point.Value
                        });
                    }, graphData);
                    this.workspace.dataSources[sourceType].data[subset][statType] = graphData;
                }
            }
        },
        
        getDataToLoad: function()    {
            // Find selected sources
            let sourcesSelected = [];
            
            for (const source in this.config.ComponentData.dataSources) {
                if ( this.config.ComponentData.dataSources[source].selected === true )  {
                    let types = [];
                    if ( ! this.config.ComponentData.dataSources[source].dataTypesLoaded )  {
                        this.config.ComponentData.dataSources[source].dataTypesLoaded = {};
                    }
                    for ( const type in this.config.ComponentData.dataSources[source].dataTypesSelected)    {
                        types.push(type);
                    }
                    if ( types.length > 0 )  {
                        sourcesSelected.push({ source: source, sourceTypes: types});
                    }
                }
            }  
            
            let dataToLoad = {};

            sourcesSelected.forEach(function(source)    {
               for ( const type in source.sourceTypes )    {
                    // If not loaded already, list to load
                    if ( ! this.workspace.dataSources[source.source].data[source.sourceTypes[type]] )  {
                        if ( ! dataToLoad[source.source] )    {
                            dataToLoad[source.source] = [];
                        }
                        dataToLoad[source.source].push(parseInt(source.sourceTypes[type]));
                    }
                }
            }.bind(this), dataToLoad);

            return dataToLoad;
        },
        
        dataLoadComplete: function () {
            // If still waiting for all loads to finish, exit
            if ( this.dataToLoad > 0 )  {
                return;
            }
            console.log(this.tabID);
            // Element may be in different window
            const ctx = this.$el.ownerDocument.getElementById('simpleChart_' + this.tabID)
                    .getContext('2d');
            
            let data = {labels: [], datasets: []};
            for (const key in this.config.ComponentData.dataSources) {
                if ( this.config.ComponentData.dataSources[key].selected === false )  {
                    continue;
                }
                let offset = this.config.ComponentData.dataSources[key].Offset || 0;
                for (const type in this.config.ComponentData.dataSources[key].dataTypesSelected) {
                    if ( ! this.config.ComponentData.dataSources[key].dataTypesSelected[type] )    {
                        continue;
                    }                    
                    for ( const subset in this.workspace.dataSources[key].data) {
                        let name = this.workspace.dataSources[key].Name
                                    + "(" + this.workspace.dataTypes[type].Name + ")";
                        if ( subset != '' ) {
                            name += "[" + subset + "]";
                        }
                        let graphData = [];
                        for ( const index in this.workspace.dataSources[key].data[subset][type].data )    {
                            let point = this.workspace.dataSources[key].data[subset][type].data[index];
                            graphData.push({ x: point.x + offset,
                                y: point.y
                            });
                        }
                        let newDataSet = { 
                            label: name,
                            data: graphData,
                            backgroundColor: this.workspace.dataSources[key].data[subset][type].colour,
                        }
                        data.datasets.push(newDataSet);
                    }
                }
            }
            this.chartElement = new Chart(ctx, {
                type: 'scatter',
                data: data,
                options: {
                    scales: {
                        xAxes: [{
                            ticks: {
                                // Display xaxis as dates
                                callback: function(value) { 
                                    let date = new Date(value);
                                    return date.toLocaleDateString()
                                             + " " + date.toLocaleTimeString(); 
                                },
                            }
                        }]
                    },
                    
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                let date = new Date(tooltipItem.xLabel);
                                let dateFormat = date.toLocaleDateString()
                                             + " " + date.toLocaleTimeString();
                                let decimals = tooltipItem.xLabel % 1000;
                                if ( decimals != 0 )    {
                                    dateFormat += "." + decimals.toLocaleString (
                                        'en-GB', {minimumIntegerDigits: 2, 
                                        useGrouping:false}
                                    );
                                }
                                return dateFormat + " - " + tooltipItem.yLabel;
                            }
                        }
                    },
                    animation: {
                        duration: 0
                    },
                }
            }); 
        },
        
        randomColor: function() {
            return "rgb(" + Math.floor(Math.random() * 255) + 
                "," + Math.floor(Math.random() * 255) + 
                "," + Math.floor(Math.random() * 255) + ")";
         }
    },
    
    template: `
    <div class="container-fluid architect-pane">
        <canvas :id="'simpleChart_' + tabID"></canvas>
    </div>
    `
})
