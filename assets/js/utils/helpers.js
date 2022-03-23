/**
 * Frequent DOM functions
 */
const dom = {

    get(selector) {
        const nodes = this.getAll(selector);

        // Return 1st array element
        return nodes.length ? nodes[0] : false;
    },

    getAll(selector) {
        return [].slice.call(document.querySelectorAll(selector));
    },

    addClass(node, className) {
        node.classList.add(className);
    },

    /**
     * Set class on target DOM node
     * - Same like addClass, but here we pass selector
     * @param  {[type]} selector  [description]
     * @param  {[type]} className [description]
     * @return {[type]}           [description]
     */
    setClass(selector, className) {
        this.get(selector).classList.add(className);
    },


    /**
     * Remove class from DOM node
     * 
     * @param  {[type]} node      [description]
     * @param  {[type]} className [description]
     * @return {[type]}           [description]
     */
    removeClass(node, className) {
        node.classList.remove(className);
    },

    /**
     * Unset class on target DOM node
     * 
     * Same like removeClass, but here we pass selector.
     * 
     * @param  {string} selector  Selector
     * @param  {[type]} className [description]
     * @return {[type]}           [description]
     */
    unsetClass(selector, className) {

        if ( ! this.get(selector) )
        {
            console.warn('Element with selector ${selector}, does not exist');
            return;
        }
        
        this.get(selector).classList.remove(className);
    },


    /**
     * getBoundingClientRect for a single node
     * @param  {[type]} selector [description]
     * @return {[type]}          [description]
     */
    getRect(selector) {
        return this.get(selector).getBoundingClientRect();
    },

    /**
     * getBoundingClientRect for a single node
     * @param  {[type]} selector [description]
     * @return {[type]}          [description]
     */
    getData(node, attr) {
        return node.dataset[attr];
    },
}

export default dom;