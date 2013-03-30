/* 

 Ionize main menu intialization

*/

var Ionize = (Ionize || {});

Ionize.initializeDesktop = function(){

	MUI.create({
		'control':'MUI.Desktop',
		'id':'desktop',
		'taskbar':true,
		'content':[
			{name:'header', url: admin_url + 'desktop/get_header'},
			{name:'taskbar'},
			{name:'content',columns:[
				{id: 'sideColumn', placement: 'left', width: 280, resizeLimit: [222, 600],
					panels:[
						{
							id: 'structurePanel',
							title: '',
							cssClass: 'panelAlt',
							content: [
								{url: admin_url + 'tree'},
								{
									name: 'toolbox',
									position: 'header',
									cssClass: 'left',
									divider: false,
									url: admin_url + 'desktop/get/toolboxes/structure_toolbox'
								}
							]
						}
					]
				},
				{id: 'mainColumn',	placement: 'main', resizeLimit: [100, 300],
					panels:[
					{
						id: 'mainPanel',
						title: Lang.get('ionize_title_welcome'),
						content: [
							{url: admin_url + 'dashboard'},
							{
								name: 'toolbox',
								position: 'header',
								url: admin_url + 'desktop/get/toolboxes/empty_toolbox'
							}
						],
						collapsible: false,
						onLoaded: function()
						{
						}
	//					,onResize: Ionize.updateResizeElements
					}]
				}
			]}
		]
	});
};



// Initialize MochaUI when the DOM is ready
window.addEvent('load', function()
{
	MUI.initialize({path:{root:theme_url + 'javascript/mochaui/'}});
	MUI.register('MUI.Windows', MUI.Windows);

	Ionize.initializeDesktop();

	Ionize.User.initialize();


//	console.log(Ionize.User.getUser());
//	console.log(Ionize.User.getGroupLevel());

});

