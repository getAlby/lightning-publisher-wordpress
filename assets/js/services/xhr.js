/**
 * WP AJAX request
 */
class LNPXHR
{
    constructor(formData = {})
    {
        // Defautl data to send to WP AJAX
        this.data = {
            nonce: cs.nonce,
            action: 'do_process_form',
        };

        Object.assign(this.data, formData);
    }


    /**
     * Make XHR Request
     */
    request()
    {
        const that = this;
        const response = await fetch(
            that.ajax_url, // window.cs defiend in WordPress enqueue
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                },
                body: that.getURLQuery(),
                credentials: 'same-origin',
            }
        )
        .then( data => data.json() )
        .then((data) => {
            return data;
        });

        return response;
    }


    /**
     * Construct query string from data object
     */
    getURLQuery()
    {
        return Object.keys(data)
            .map(function (k) {
                return `${encodeURIComponent(k)}=${encodeURIComponent(data[k])}`;
            })
            .join('&');
    }


    getAJAXURL()
    {
        return this.endpoint;
    }
}

export default LNPXHR;