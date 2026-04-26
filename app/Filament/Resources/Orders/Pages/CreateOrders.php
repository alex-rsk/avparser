<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrdersResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\{Customer, AvitoCategory};
use Illuminate\Support\Facades\Log;

class CreateOrders extends CreateRecord
{
    protected static string $resource = OrdersResource::class;

    public ?int $selectedCategoryId = null;

    public function selectCategory(int $id): void
    {
        $this->selectedCategoryId = $id;    
        Log::channel('daily')->debug('select category : '.$id);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            Log::channel('daily')->debug('Selected category : '.$this->selectedCategoryId);
            $category = AvitoCategory::query()->where('id' , intval($this->selectedCategoryId))->first();
            if (is_null($category)) {
                throw new \Exception ("not found");
            }

            $data['category_title'] = $category->title;
            $data['category_id'] = $category->id;
            $data['customer_id'] = Customer::firstOrFail()?->id;            
            $data['title']  = 'Заказ на '.$category->title;
            $data['status'] = 'new';
            return $data;
        }
        catch (\Exception $ex) 
        {
            Log::channel('daily')->warning('Category not found:'.$this->selectedCategoryId. ', Error:'.$ex->getMessage());
            return [];
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),        // "Create" button
            $this->getCancelFormAction(),         // "Cancel" button
        ];
    }
}
