<?php

namespace App\Http\Livewire;

use App\Models\DeliveryArea;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Manny;

class DeliveryAreaForm extends Component
{
    use AuthorizesRequests;

    public $state;
    public $deliveryArea;
    public $confirmingDelete = false;

    public function save()
    {
        $data = $this->state;
        if (isset($data['initial'])) {
            $data['initial'] = preg_replace('/[^0-9]/', '', $data['initial']);
        }

        if (isset($data['final'])) {
            $data['final'] = preg_replace('/[^0-9]/', '', $data['final']);
        }

        if (isset($data['price'])) {
            $data['price'] = DeliveryArea::priceToFloat($data['price']);
        }

        $initialDataValidator = ['required', 'regex:/^[0-9]{8}$/i', 'lte:final'];
        if ($this->deliveryArea) {
            $initialDataValidator[] = 'unique:delivery_areas,initial,' . $this->deliveryArea->id;
        } else {
            $initialDataValidator[] = 'unique:delivery_areas';
        }

        $finalDataValidator = ['required', 'regex:/^[0-9]{8}$/i', 'gte:initial'];
        if ($this->deliveryArea) {
            $finalDataValidator[] = 'unique:delivery_areas,final,' . $this->deliveryArea->id;
        } else {
            $finalDataValidator[] = 'unique:delivery_areas';
        }

        Validator::make($data, [
            'initial' => $initialDataValidator,
            'final' => $finalDataValidator,
            'price' => ['nullable', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'prevent' => ['nullable', 'boolean'],
        ])->validate();

        if (!$this->deliveryArea) {
            $this->authorize('deliveryArea:create');
            DeliveryArea::create($data);
            session()->flash('flash.banner', __('Delivery Area successfully created!'));
        } else {
            $this->authorize('deliveryArea:update');
            $this->deliveryArea->update($data);
            session()->flash('flash.banner', __('Delivery Area successfully updated!'));
        }

        return redirect()->route("deliveryArea.index");
    }

    public function updated($field)
    {
        if ($field == "state.initial") {
            $this->state['initial'] = Manny::mask($this->state['initial'], '11111-111');
        }

        if ($field == "state.final") {
            $this->state['final'] = Manny::mask($this->state['final'], '11111-111');
        }

        if ($field == "state.price") {
            $this->state['price'] = DeliveryArea::floatToPrice(DeliveryArea::priceToFloat($this->state['price']));
        }
    }

    public function delete()
    {
        $this->authorize('deliveryArea:delete');
        $this->deliveryArea->delete();
        session()->flash('flash.banner', __('Delivery Area successfully deleted!'));
        return redirect()->route("deliveryArea.index");
    }

    public function mount()
    {
        if ($this->deliveryArea) {
            $this->state = [
                'initial' => $this->deliveryArea->initial,
                'final' => $this->deliveryArea->final,
                'price' => $this->deliveryArea->price,
                'prevent' => $this->deliveryArea->getRawOriginal('prevent'),
            ];
        }
    }

    public function render()
    {
        return view('delivery_areas.delivery-area-form');
    }
}