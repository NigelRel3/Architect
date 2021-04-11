/*
Charts using https://www.chartjs.org
 */
var simplegraph = Vue.component('simplegraph', {
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
                                graphData.labels.push( point.Timestamp );
                                graphData.data.push( point.Value );
                    }, graphData);
                    this.workspace.dataSources[sourceType].data[subset][statType] = graphData;
//                    this.config.ComponentData.dataSources[sourceType].dataTypesLoaded[statType] = true;
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
        
        getLabels: function ()  {
            let labels = [];
            // Foreach source
            for (var key in this.config.ComponentData.dataSources) {
                if ( this.config.ComponentData.dataSources[key].selected )  {
                    let offset = this.config.ComponentData.dataSources[key].Offset || 0;
                   // datapoint type within source
                    for (var type in this.config.ComponentData.dataSources[key].dataTypesSelected) {
                        if ( this.config.ComponentData.dataSources[key].dataTypesSelected[type])    {
                            for ( const subset in this.workspace.dataSources[key].data) {
                                for ( const label of this.workspace.dataSources[key].data[subset][type].labels )  {
                                    labels.push(parseInt(label) + offset)
                                }
                            }
                        }
                    }
                }
            }
            labels = [...new Set(labels)];
            labels.sort();
            return labels;
        },
        
        dataLoadComplete: function () {
            // If still waiting for all loads to finish, exit
            if ( this.dataToLoad > 0 )  {
                return;
            }
            let ctx = document.getElementById('simpleChart_' + this.tabID)
                .getContext('2d');
            
            let data = {labels: [], datasets: []};
            data.labels = this.getLabels();
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
                        let graphData = this.mergeData(data.labels, 
                            this.workspace.dataSources[key].data[subset][type],
                            offset);
                        let newDataSet = { 
                            label: name,
                            data: graphData,
                            backgroundColor: this.workspace.dataSources[key].data[subset][type].colour,
                            lineTension: 0,
                            fill: false
                        }
                        data.datasets.push(newDataSet);
                    }
                }
            }
            data.labels = this.trimLabels(data.labels);            
            this.chartElement = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {}
            }); 
        },
        
        mergeData: function ( labels, data, offset )    {
            let dataKey = 0;
            let output = [];
            for ( const labelKey in labels )    {
                let adjustedTime = parseInt(data.labels[dataKey]) + offset;
                if ( labels[labelKey] < parseInt(data.labels[dataKey]) + offset )   {
                    output.push(null);
                }
                else    {
                    output.push(data.data[dataKey++]);
                }
            }
            
            return output;
        },
        // Remove date/hours/minute if repeated in label
        trimLabels: function ( labels ) {
            // Convert timestamp labels to textual date format
            let graphLabels = [];
            labels.forEach( function (timestamp) {
                let date = new Date(parseInt(timestamp));
                graphLabels.push(date.toLocaleDateString() + " " + date.toLocaleTimeString()
                                + "." + ('00' + date.getMilliseconds()).slice(-3));
            }, graphLabels);
            prevDate = '';
            prevTime = '';
            for ( const date in graphLabels )    {
                let parts = graphLabels[date].split(' ');
                let newLabel = parts[0];
                if ( parts[0] == prevDate ) {
                    newLabel = parts[1];
                    // And time
                    let timeParts = newLabel.split(":");
                    if ( timeParts[0] + ":" + timeParts[1] == prevTime )    {
                        newLabel = timeParts[2];
                    }
                    graphLabels[date] = newLabel
                    prevTime = timeParts[0] + ":" + timeParts[1];
                }
                prevDate = parts[0];
            }   
            
            return graphLabels 
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
