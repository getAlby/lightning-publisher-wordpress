class LNP_DonationsWidget
{
    constructor( selector )
    {
        /**
         * Available Endpoints
         *
         * @base : Base LNP Alby endpoint
         * @donate : Create invoice
         * @verify : Check/Verify payment has been made
         */
        this.endpoints = {
            base: window.LN_Paywall.rest_base,
            donate: window.LN_Paywall.rest_base + '/donate',
            verify: window.LN_Paywall.rest_base + '/verify',
        }

        // Widget wrapper CSS class
        this.wrapperClass = selector;

        // Invoice data
        this.invoice = {}

        // Init button listeners
        this.init();
    }


    /**
     * Attach listeners to donate buttons
     */
    init()
    {
        this.buttons       = this.getAll('.wpl-donate-button');
        const countWidgets = this.buttons.length;
        const that         = this;

        for (let i = 0; i < countWidgets; i++)
        {
            const button = that.buttons[i];

            button.addEventListener('click', function(ev) {
                ev.preventDefault();

                // Define wrapper
                that.wrapper = this.closest(that.wrapperClass);

                // Trigger payment request
                that.requestPayment();
            });
        }
    }


    /**
     * Get Donation amount from closest .amount input field
     */
    getAmount()
    {
        const wrapper = this.wrapper;
        const input   = wrapper.querySelector('.wpl-donate-amount');

        return parseInt(input.value);
    }

    getPostID()
    {
        const wrapper = this.wrapper;
        const button  = wrapper.querySelector('button[data-post-id]');

        return parseInt(button.dataset.postId);
    }



    /**
     *
     * API Calls
     * 
     */
    

    /**
     * Create invoice when donate button is clicked
     */
    requestPayment()
    {   
        const that     = this;
        const response = this.postRequest('donate', {
            amount: that.getAmount(),
            post_id: that.getPostID(),
        });

        // In case error exists
        that.clearError();

        // Make a request to API
        response.then( data => {

            if ( 'token' in data )
            {
                that.setInvoice(data);
                return; // Stop here
            }
            
            that.clearInvoice();
            that.showError('Error creating invoice');
        });
    }
    

    /**
     * Fetch wrapper
     *
     * @endpoint string One of available endpoints, see in constructor
     * @data object Data object to send to endpoint
     */
    async postRequest( endpoint = '', data = {} )
    {
        const url = this.endpoints[ endpoint ];
        const response = await fetch(url, {
            method: 'POST',
            cache: 'no-cache',
            headers: {
                'Content-Type': 'application/json'
            },
            referrerPolicy: 'no-referrer',
            body: JSON.stringify(data)
        });
        
        return response.json();
    }



    /**
     * Helpers
     */
    

    setInvoice( data )
    {
        this.invoice = data;
    }
    
    // Reset invoice
    clearInvoice()
    {
        this.invoice = {}
    }


    setError( message )
    {
        const errorDiv = this.wrapper.querySelector('.error');

        if ( errorDiv )
        {
            errorDiv.innerHTML = message;
        }
    }


    clearError()
    {
        const errorDiv = this.wrapper.querySelector('.error');

        if ( errorDiv )
        {
            errorDiv.innerHTML = null;
        }
    }

    /**
     * Utils for DOM manipulation
     */
    
    // document.querySelector() wrapper
    get(selector)
    {
        const nodes = this.getAll(selector);

        // Return 1st array element
        return nodes.length ? nodes[0] : false;
    }

    /**
     * document.querySelectorAll() wrapper
     * 
     * @param  {string} selector
     * @return {array}  Array of all elements
     */
    getAll(selector)
    {
        return [].slice.call(document.querySelectorAll(selector));
    }
}

new LNP_DonationsWidget('.wp-lnp-donations-wrapper');