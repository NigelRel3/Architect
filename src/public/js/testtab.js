var testtab = Vue.component('testtab', {
    data: function() {
        return {
            a: 'hello'
        }
    },
    
    props: ['workspace', 'config', 'panels', 'basepanel'],

    mounted: function () {
        // https://stackoverflow.com/questions/49657462/open-a-vuejs-component-on-a-new-window
        
        // Would opening the home page and then pushing the component 
        // in at the page level work?
        this.windowRef = window.open(""); // "/home?secondary=true");
        
        this.windowRef.addEventListener('beforeunload', this.closePortal);
        this.windowRef.document.body.appendChild(this.$el);
        
        // Close sub window when this one closes
        window.addEventListener('beforeunload', this.closePortal);
// https://stackoverflow.com/a/17089124/1213708
// post data to new window?

    },
    methods: {
        closePortal() {
          if(this.windowRef) {
            this.windowRef.close();
            this.windowRef = null;
          }
        }
    },
    
    template: `
    <div>
        New tab
        <input type="text" v-model="a" /> {{a}}
    </div>
    `
})
