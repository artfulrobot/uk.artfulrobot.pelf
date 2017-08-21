(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('pelf', [
    'crmUi', 'crmUtil', 'ngRoute'
  ])

  .filter('localeNumber', function() {
    return function(n) {
      return parseFloat(n).toLocaleString();
    };
  })

  // Kill off Drupal/Garland sidebars.
  .directive('pelfGreedy', function() {
    return {
      link: function (scope, element, attrs) {
        // These are specific to Garland theme (and probably should not be here!).
        $('body').addClass('pelf').removeClass('one-sidebar sidebar-first');
      }
    };
  })

  .factory('pelf', ['crmApi', function(crmApi) {
    // Returns a promise of an Pelf object.
    console.log("pelf factory");

    // Define the Pelf class.
    var Pelf = function(pelfConfig) {
      _.extend(this, pelfConfig);
    };
    Pelf.prototype.foo = function(a) { console.log("foo ", a); };
    Pelf.prototype.getConfig = function() { console.log("config ", this.config); return this.config; };
    Pelf.prototype.friendlyProspectStage = function(machineName) {
      return this.prospect.stages[machineName];
    };

    // Fetch config, when we've got it, instantiate the object and return it.
    return crmApi('Pelf', 'GetConfig', {})
      .then(function(config) {
        console.log("Pelf resolving ");
        return new Pelf(config);
      });
  }])
  ;

})(angular, CRM.$, CRM._);
