jQuery(document).ready( function($) {
	$('.apcm-about-logo').css({opacity:1});
	
	$( "#apcm-chart-button-ratio" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).addClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).addClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-hit" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).addClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).addClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-memory" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).addClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).addClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-file" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).addClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).addClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-key" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).addClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).addClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-string" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).addClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).addClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-buffer" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).addClass( "active" );
			$( "#apcm-chart-uptime" ).removeClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).addClass( "active" );
			$( "#apcm-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#apcm-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#apcm-chart-ratio" ).removeClass( "active" );
			$( "#apcm-chart-hit" ).removeClass( "active" );
			$( "#apcm-chart-memory" ).removeClass( "active" );
			$( "#apcm-chart-file" ).removeClass( "active" );
			$( "#apcm-chart-key" ).removeClass( "active" );
			$( "#apcm-chart-string" ).removeClass( "active" );
			$( "#apcm-chart-buffer" ).removeClass( "active" );
			$( "#apcm-chart-uptime" ).addClass( "active" );
			$( "#apcm-chart-button-ratio" ).removeClass( "active" );
			$( "#apcm-chart-button-hit" ).removeClass( "active" );
			$( "#apcm-chart-button-memory" ).removeClass( "active" );
			$( "#apcm-chart-button-file" ).removeClass( "active" );
			$( "#apcm-chart-button-key" ).removeClass( "active" );
			$( "#apcm-chart-button-string" ).removeClass( "active" );
			$( "#apcm-chart-button-buffer" ).removeClass( "active" );
			$( "#apcm-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
