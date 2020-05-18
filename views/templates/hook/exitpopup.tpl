{$product->id_product|@print_r}
{$ep_product_id|@print_r}
{if $ep_active && $page.page_name == 'category'}
  {if $category.id == $ep_category_id  }
    {assign var="display" value=true}
  {/if}
{elseif $ep_active && $page.page_name == 'product' }
  {if $product->id_product == $ep_product_id}
   {assign var="display" value=true}
  {/if}
{/if}




{if $display}
  {literal}
  <style>
  .exitpopup .modal-content p,
  .exitpopup .modal-content{
    color:{/literal}{$ep_text_color}{literal}
  }
  </style>
  {/literal}
  
  <!-- Button trigger modal -->
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">open popup</button>

  <!-- Modal -->
  <div class="modal fade exitpopup" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="background-color:{$ep_main_color};color:{$ep_text_color};border-color:{$ep_text_color};width:100%;">
          <button type="button" class="close js-close-exit" data-dismiss="modal" aria-label="Close">
            <i class="far fa-times-circle"></i>
          </button>
          
      
        <div class="modal-body">
        <div class="row">
          <div class="col-xs-3" style="text-align:center;">
            <i class="far fa-hand-paper"></i>
          </div>
          <div class="col-xs-9">
            {$ep_text nofilter}
            <button type="button" class="btn btn-primary js-close-exit" style="background-color:{$ep_text_color};color:{$ep_main_color};" data-dismiss="modal">Wróć do sklepu <i class="fas fa-arrow-right"></i></button>
          </div>
        </div>
        </div>
    
      </div>
    </div>
</div>
{/if}