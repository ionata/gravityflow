(function (GravityFlowEntryDetail, $) {
    GravityFlowEntryDetail.printPage = function( sURL ) {
        printPage( sURL );
    };

    GravityFlowEntryDetail.displayDiscussionItemToggle = function (formId, fieldId, displayLimit) {

        var $toggle = $( '#field_' + formId + '_' + fieldId );

        if ( $toggle ) {

            $toggle.children( '.gravityflow-dicussion-item-hidden' ).slideToggle( 'fast' );

            var oldText = $toggle.children( '.gravityflow-dicussion-item-toggle-display' ).attr( 'title' );
            var newText = $toggle.children( '.gravityflow-dicussion-item-toggle-display' ).data( 'title' );

            $toggle.children( '.gravityflow-dicussion-item-toggle-display' ).attr( 'title', newText ).text( newText );
            $toggle.children( '.gravityflow-dicussion-item-toggle-display' ).data( 'title', oldText );

        }
    }

}(window.GravityFlowEntryDetail = window.GravityFlowEntryDetail || {}, jQuery));

function closePrint () {
    document.body.removeChild( this.__container__ );
}

function setPrint () {
    this.contentWindow.__container__ = this;
    this.contentWindow.onbeforeunload = closePrint;
    this.contentWindow.onafterprint = closePrint;
    this.contentWindow.focus();

    var ms_ie = false;
    var ua = window.navigator.userAgent;
    var old_ie = ua.indexOf( 'MSIE ' );
    var new_ie = ua.indexOf( 'Trident/' );

    if ((old_ie > -1) || (new_ie > -1)) {
        ms_ie = true;
    }

    if ( ms_ie ) {
        this.contentWindow.document.execCommand( 'print', false, null );
    } else {
        this.contentWindow.print();
    }

}

function printPage (sURL) {
    var oHiddFrame = document.createElement( "iframe" );
    oHiddFrame.onload = setPrint;
    oHiddFrame.style.visibility = "hidden";
    oHiddFrame.style.position = "fixed";
    oHiddFrame.style.right = "0";
    oHiddFrame.style.bottom = "0";
    oHiddFrame.src = sURL;
    document.body.appendChild( oHiddFrame );
}
