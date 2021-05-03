var fileChooser = Vue.component('filechooser', {
    data: function() {
        return {
            dropArea: null,
            dataFileID: null,
            fileElemID: null,
            fileDetailsID: null
        }
    },
    
    props: ['config', 'importData', 'tabID'],
    
    created: function() {
        this.dataFileID = "dataLoad" + this.tabID;
        this.fileElemID = "fileElement" + this.tabID;
        this.fileDetailsID = "fileDetails" + this.tabID;
    },
    
    mounted: function () {
        this.enableDragDrop();
    },

    updated: function () {
        this.enableDragDrop();
    },

    methods: {
        enableDragDrop: function (){
            // Drag and drop for data file
            console.log("enableDD for " + this.dataFileID);
            this.dropArea = this.$el.ownerDocument.getElementById(this.dataFileID);
            if ( this.dropArea )    {
                console.log("enabledDD!!!");
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    this.dropArea.addEventListener(eventName, this.preventDefaults.bind(this), false);
                });
                ['dragenter', 'dragover'].forEach(eventName => {
                    this.dropArea.addEventListener(eventName, this.highlight.bind(this), false);
                });
        
                ['dragleave', 'drop'].forEach(eventName => {
                    this.dropArea.addEventListener(eventName, this.unhighlight.bind(this), false);
                });
        
                this.dropArea.addEventListener('drop', this.handleDrop.bind(this), false);
                console.log("enable end for " + this.dataFileID);
            }
        },
        
        preventDefaults: function(e) {
            e.preventDefault()
            e.stopPropagation()
        },
        
        highlight: function() {
            this.dropArea.classList.add('highlight');
        },
    
        unhighlight: function() {
            this.dropArea.classList.remove('highlight');
        },
        
        handleDrop: function(e) {
            let dt = e.dataTransfer;
            let files = dt.files;
    
            this.displayFileDetails(files[0]);
        },

        displayFileDetails: function(file) {
            let fileDate = new Date();
            fileDate.setTime(file.lastModified);
            let dateString = fileDate.toLocaleDateString() + " "
                                + fileDate.toLocaleTimeString();
            let fileDetails = "(File selected: " + file.name + 
                    " Last modified:" + dateString + ")";
            $('#' + this.fileDetailsID).text(fileDetails);
            
            // Add file to import
            // TODO what if add several times?
            this.importData.append('fileAttachment', file);
        },
        
        changeFileSelection: function(event) {
            let files = event.target.files;
            this.displayFileDetails(files[0]);
        },

        selectSource: function()    {
            $('#' + this.fileElemID).click();
        }
    },
    
    template: `
    <div id="filechooser">
        <div :id="dataFileID" class="drop-area col-sm-4 text-center"
            @click="selectSource">
            Click to select source data file (or drop file here)
            <input class="hide" type="file" 
                :id="fileElemID"
                :accept="config.types" 
                @change="changeFileSelection" />
            <span :id="fileDetailsID" />
        </div>
    </div>
    `,

})

