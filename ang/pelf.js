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
    Pelf.prototype.fundingCalcs = function(activity) {
      console.log("Running fundingCalcs on ", activity.funding);
      activity.sumFunding = Math.round(_.reduce(activity.funding, function(tot, row) { return tot+parseFloat(row.amount); }, 0) ,0);
      if ('scale' in activity) {
        // If we have this, it's a prospect, calculate estWorth.
        activity.estWorth = Math.round(activity.sumFunding  * activity.scale / 100, 0);
      }
    };

    // Fetch config, when we've got it, instantiate the object and return it.
    return crmApi('Pelf', 'GetConfig', {})
      .then(function(config) {
        var pelf = new Pelf(config);
        console.log("Pelf resolved: ", pelf);
        return pelf;
      });
  }])
  ;

})(angular, CRM.$, CRM._);
